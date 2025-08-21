<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

class Read_Offline_Export {
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
	}

	public static function register_rest() {
		register_rest_route( 'read-offline/v1', '/export', [ 
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_export' ],
			'args'                => [ 
				'postId' => [ 'required' => true, 'type' => 'integer' ],
				'format' => [ 'required' => true, 'type' => 'string', 'enum' => [ 'pdf', 'epub' ] ],
				'nonce'  => [ 'required' => false, 'type' => 'string' ],
			],
			'permission_callback' => '__return_true',
		] );
	}

	public static function rest_export( WP_REST_Request $req ) {
		$post_id = (int) $req->get_param( 'postId' );
		$format  = $req->get_param( 'format' );
		$nonce   = $req->get_param( 'nonce' );

		$post = get_post( $post_id );
		if ( ! $post || ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post_id ) ) ) {
			return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}
		// Require nonce only for non-public content; allow published content without nonce
		if ( 'publish' !== $post->post_status ) {
			if ( ! current_user_can( 'read_post', $post_id ) && ! wp_verify_nonce( $nonce, 'read_offline_export_' . $post_id ) ) {
				return new WP_REST_Response( [ 'error' => 'forbidden' ], 403 );
			}
		}

		$path = self::generate( $post_id, $format );
		if ( is_wp_error( $path ) || ! $path ) {
			$code = is_wp_error( $path ) ? $path->get_error_code() : 'generation_failed';
			return new WP_REST_Response( [ 'error' => $code ], 500 );
		}
		$url = self::path_to_url( $path );
		return new WP_REST_Response( [ 'status' => 'ok', 'url' => $url ], 200 );
	}

	public static function generate( $post_id, $format ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'not_found', 'Post not found' );
		}
		$hash    = self::compute_hash( $post, $format );
		$uploads = wp_upload_dir();
		$subdir  = '/read-offline/' . date( 'Y/m', current_time( 'timestamp' ) );
		$dir     = trailingslashit( $uploads[ 'basedir' ] ) . ltrim( $subdir, '/' );
		wp_mkdir_p( $dir );

		$filename = self::build_filename( $post, $format, $hash );
		$path     = trailingslashit( $dir ) . $filename;

		if ( file_exists( $path ) ) {
			return $path;
		}

		// Prepare content
		$title   = get_the_title( $post );
		$content = apply_filters( 'the_content', $post->post_content );
		$content = apply_filters( 'read_offline_content_html', $content, $post, $format );

		if ( $format === 'pdf' ) {
			return self::generate_pdf( $post, $title, $content, $path );
		}
		if ( $format === 'epub' ) {
			return self::generate_epub( $post, $title, $content, $path );
		}
		return new WP_Error( 'invalid_format', 'Invalid format' );
	}

	protected static function generate_pdf( WP_Post $post, $title, $html, $path ) {
		$pdf_opts = get_option( 'read_offline_settings_pdf', [] );
		$gen_opts = get_option( 'read_offline_settings_general', [] );
		$size     = $pdf_opts[ 'size' ] ?? 'A4';
		$m        = $pdf_opts[ 'margins' ] ?? [ 't' => 15, 'r' => 15, 'b' => 15, 'l' => 15 ];
		$css      = "img{max-width:100%;height:auto;} figure{margin:0;}" . ( $gen_opts[ 'css' ] ?? '' );
		$css .= apply_filters( 'read_offline_pdf_css', '', $post );

		if ( class_exists( '\\Mpdf\\Mpdf' ) ) {
			try {
				// Support custom size like "210x297" (mm) when size is set to Custom
				$formatArg = $size;
				if ( is_string( $size ) && strtolower( $size ) === 'custom' ) {
					$custom = isset( $pdf_opts[ 'custom_size' ] ) ? trim( (string) $pdf_opts[ 'custom_size' ] ) : '';
					if ( $custom && preg_match( '/^\s*(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*$/i', $custom, $mm ) ) {
						$w = (float) $mm[ 1 ];
						$h = (float) $mm[ 2 ];
						if ( $w > 0 && $h > 0 ) {
							$formatArg = [ $w, $h ];
						}
					}
				}
				$mpdf = new \Mpdf\Mpdf( [ 
					'format'        => $formatArg,
					'margin_left'   => (int) ( $m[ 'l' ] ?? 15 ),
					'margin_right'  => (int) ( $m[ 'r' ] ?? 15 ),
					'margin_top'    => (int) ( $m[ 't' ] ?? 15 ),
					'margin_bottom' => (int) ( $m[ 'b' ] ?? 15 ),
				] );
				$mpdf->SetTitle( $title );
				$mpdf->SetAuthor( get_bloginfo( 'name' ) );

				// Header/footer
				if ( ! empty( $pdf_opts[ 'header' ] ) )
					$mpdf->SetHTMLHeader( $pdf_opts[ 'header' ] );
				if ( ! empty( $pdf_opts[ 'footer' ] ) ) {
					$mpdf->SetHTMLFooter( $pdf_opts[ 'footer' ] );
				} elseif ( ! empty( $pdf_opts[ 'page_numbers' ] ) ) {
					$mpdf->SetFooter( '{PAGENO}/{nbpg}' );
				}

				// Printable toggle
				if ( isset( $pdf_opts[ 'printable' ] ) && ! $pdf_opts[ 'printable' ] ) {
					$mpdf->SetProtection( [ 'copy' ] );
				} else {
					$mpdf->SetProtection( [ 'print' ] );
				}
				// Watermark
				if ( ! empty( $pdf_opts[ 'watermark' ] ) ) {
					$mpdf->SetWatermarkText( $pdf_opts[ 'watermark' ] );
					$mpdf->showWatermarkText = true;
				}

				// TOC before content
				if ( ! empty( $pdf_opts[ 'toc' ] ) ) {
					$depth = max( 1, min( 6, (int) ( $pdf_opts[ 'toc_depth' ] ?? 3 ) ) );
					$h2toc = [];
					for ( $i = 1; $i <= $depth; $i++ ) {
						$h2toc[ 'H' . $i ] = $i - 1;
					}
					$mpdf->h2toc = $h2toc;
					$mpdf->TOCpagebreakByArray( [ 
						'toc-preHTML' => '<h1>' . esc_html( __( 'Contents', 'read-offline' ) ) . '</h1>',
						'links'       => 1,
					] );
				}

				// Write CSS + HTML
				$mpdf->WriteHTML( '<style>' . $css . '</style>' . $html );
				$mpdf->Output( $path, \Mpdf\Output\Destination::FILE );
				return $path;
			} catch (\Throwable $e) {
				return new WP_Error( 'pdf_failed', $e->getMessage() );
			}
		}
		// Fallback: save HTML with .pdf name to signal missing dependency
		file_put_contents( $path, $html );
		return new WP_Error( 'mpdf_missing', 'mPDF not available. Install dependencies.' );
	}

	protected static function generate_epub( WP_Post $post, $title, $html, $path ) {
		$epub_opts  = get_option( 'read_offline_settings_epub', [] );
		$meta       = $epub_opts[ 'meta' ] ?? [];
		$author     = $meta[ 'author' ] ?? get_bloginfo( 'name' );
		$publisher  = $meta[ 'publisher' ] ?? get_bloginfo( 'name' );
		$lang       = $meta[ 'lang' ] ?? get_locale();
		$profile    = $epub_opts[ 'css_profile' ] ?? 'light';
		$custom_css = $epub_opts[ 'custom_css' ] ?? '';

		$css = '';
		switch ( $profile ) {
			case 'light':
				$css = 'body{font-family: serif;line-height:1.6;color:#222;background:#fff;padding:1em;} img{max-width:100%;height:auto;} h1,h2,h3{margin-top:1.2em;}';
				break;
			case 'dark':
				$css = 'body{font-family: serif;line-height:1.6;color:#f5f5f5;background:#111;padding:1em;} a{color:#9cf;} img{max-width:100%;height:auto;} h1,h2,h3{margin-top:1.2em;}';
				break;
			case 'custom':
				$css = (string) $custom_css;
				break;
			case 'none':
			default:
				$css = '';
		}
		$css = apply_filters( 'read_offline_epub_css', $css, $post, $epub_opts );

		// Optional inline TOC (EPUB-friendly div, not HTML5 nav), honoring toc_depth
		$prefixToc = '';
		if ( ! empty( $epub_opts[ 'toc' ] ) ) {
			$depth                  = max( 1, min( 6, (int) ( $epub_opts[ 'toc_depth' ] ?? 3 ) ) );
			list( $html, $tocHtml ) = self::build_epub_toc_html( $html, $depth );
			if ( $tocHtml ) {
				$tocHtml   = apply_filters( 'read_offline_epub_toc_html', $tocHtml, $post, $depth );
				$prefixToc = '<div class="read-offline-epub-toc-wrap"><h1>' . esc_html( __( 'Contents', 'read-offline' ) ) . '</h1>' . $tocHtml . '</div>';
			}
		}

		$bodyHtml = $prefixToc . $html;
		$xhtml    = self::wrap_epub_xhtml_document( $title, $lang, $bodyHtml, $css );

		$dir      = dirname( $path );
		$basename = preg_replace( '/\.epub$/', '', basename( $path ) );

		if ( class_exists( '\\PHPePub\\Core\\EPub' ) ) {
			try {
				$book = new \PHPePub\Core\EPub();
				$book->setTitle( $title );
				$book->setIdentifier( get_permalink( $post ), \PHPePub\Core\EPub::IDENTIFIER_URI );
				$book->setLanguage( $lang );
				$book->setAuthor( $author, $author );
				$book->setPublisher( $publisher, get_site_url() );
				$book->setSourceURL( get_permalink( $post ) );

				// Cover
				$cover = apply_filters( 'read_offline_epub_cover', null, $post, $epub_opts );
				if ( ! $cover ) {
					$cover = self::resolve_cover_image( $post, $epub_opts[ 'cover' ] ?? 'featured', $epub_opts );
				}
				if ( $cover && method_exists( $book, 'setCoverImage' ) ) {
					list( $filename, $data, $mime ) = $cover;
					$book->setCoverImage( $filename, $data, $mime );
				}

				// Chapter (single) with valid XHTML
				$book->addChapter( 'content', 'chapter1.xhtml', $xhtml );

				$book->finalize();
				$book->saveBook( $basename, $dir );
				return $path;
			} catch (\Throwable $e) {
				return new WP_Error( 'epub_failed', $e->getMessage() );
			}
		}
		file_put_contents( $path, $html );
		return new WP_Error( 'phpepub_missing', 'PHPePub not available. Install dependencies.' );
	}

	// Build a well-formed XHTML document for EPUB content
	protected static function wrap_epub_xhtml_document( $title, $lang, $bodyHtml, $css = '' ) {
		// Normalize entities, unescaped ampersands in attributes, and self-close void elements for XHTML parsers
		$bodyHtml = self::normalize_xml_entities_for_epub( $bodyHtml );
		$bodyHtml = self::normalize_xml_ampersands_in_attributes( $bodyHtml );
		$bodyHtml = self::repair_html_fragment_with_dom( $bodyHtml );
		$bodyHtml = self::normalize_xhtml_attributes_quoted( $bodyHtml );
		$bodyHtml = self::normalize_xhtml_void_elements( $bodyHtml );
		$headCss  = $css ? ( '<style type="text/css">' . $css . '</style>' ) : '';
		$doc      = '';
		$doc .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$doc .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n";
		$doc .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . esc_attr( $lang ) . '" lang="' . esc_attr( $lang ) . '">';
		$doc .= '<head>';
		$doc .= '<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />';
		$doc .= '<title>' . esc_html( $title ) . '</title>';
		$doc .= $headCss;
		$doc .= '</head>';
		$doc .= '<body>' . $bodyHtml . '</body>';
		$doc .= '</html>';
		// If Tidy is available, use it to ensure strict XHTML output
		$tidied = self::maybe_tidy_xhtml( $doc );
		$final  = $tidied ?: $doc;
		// Final safeguard: ensure void elements are self-closed in the final XHTML
		$final = self::normalize_xhtml_void_elements( $final );
		return $final;
	}

	// Ensure attributes on void elements are quoted per XHTML rules
	protected static function normalize_xhtml_attributes_quoted( $html ) {
		$tags    = [ 'img', 'source', 'track', 'input', 'meta', 'link', 'area', 'base', 'col', 'embed', 'param', 'br', 'hr', 'wbr' ];
		$pattern = '/<(' . implode( '|', $tags ) . ')\b([^>]*?)\/?>(?!\s*<\/\1>)/i';
		return preg_replace_callback( $pattern, function ($m) {
			$tag   = strtolower( $m[ 1 ] );
			$attrs = trim( $m[ 2 ] );
			if ( $attrs === '' ) {
				return '<' . $tag . ' />';
			}
			$rebuilt   = [];
			$regexAttr = '/([:\w-]+)(?:\s*=\\s*(?:"([^"]*)"|\'([^\']*)\'|([^\\s"\'<>/]+)))?/';
			if ( preg_match_all( $regexAttr, $attrs, $am, PREG_SET_ORDER ) ) {
				foreach ( $am as $a ) {
					$name  = strtolower( $a[ 1 ] );
					$value = '';
					if ( isset( $a[ 3 ] ) && $a[ 3 ] !== '' )
						$value = $a[ 3 ];
					elseif ( isset( $a[ 4 ] ) && $a[ 4 ] !== '' )
						$value = $a[ 4 ];
					elseif ( isset( $a[ 5 ] ) && $a[ 5 ] !== '' )
						$value = $a[ 5 ];
					// XHTML boolean attributes must be name="name"
					if ( $value === '' )
						$value = $name;
					$value     = str_replace( '"', '&quot;', $value );
					$rebuilt[] = $name . '="' . $value . '"';
				}
			}
			return '<' . $tag . ' ' . implode( ' ', $rebuilt ) . ' />';
		}, $html );
	}

	// Try to repair malformed HTML fragments using DOMDocument, returning a cleaner HTML string
	protected static function repair_html_fragment_with_dom( $html ) {
		if ( ! class_exists( '\\DOMDocument' ) ) {
			return $html;
		}
		$dom  = new \DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$opts = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING;
		// Ensure UTF-8 handling
		$loaded = $dom->loadHTML( '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $html, $opts );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		if ( ! $loaded ) {
			return $html;
		}
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body ) {
			return $html;
		}
		$out = '';
		foreach ( $body->childNodes as $child ) {
			$out .= $dom->saveHTML( $child );
		}
		return $out !== '' ? $out : $html;
	}

	protected static function normalize_xhtml_void_elements( $html ) {
		$tags = [ 'img', 'br', 'hr', 'input', 'meta', 'link', 'source', 'track', 'wbr', 'area', 'base', 'col', 'embed', 'param' ];
		foreach ( $tags as $t ) {
			$pattern = '/<' . $t . '\b((?>[^"\'<>]|"[^"]*"|\'[^\']*\')*)>/i';
			$html    = preg_replace_callback( $pattern, function ($m) use ($t) {
				$attrs = rtrim( $m[ 1 ] );
				// Already self-closed? (<tag .../>)
				if ( preg_match( '/\/$/', $attrs ) ) {
					$attrs = rtrim( substr( $attrs, 0, -1 ) );
					$space = $attrs !== '' ? ' ' : '';
					return '<' . strtolower( $t ) . $space . $attrs . ' />';
				}
				$space = $attrs !== '' ? ' ' : '';
				return '<' . strtolower( $t ) . $space . $attrs . ' />';
			}, $html );
			// Remove any erroneous closing tags for void elements: </tag>
			$html = preg_replace( '/<\/' . $t . '\s*>/i', '', $html );
		}
		return $html;
	}

	protected static function normalize_xml_ampersands_in_attributes( $html ) {
		// Replace raw & in attribute values with &amp; unless already an entity
		return preg_replace_callback( '/(\w+)=("|")(.*?)(\2)/s', function ($m) {
			$before = $m[ 1 ] . '=' . $m[ 2 ];
			$val    = $m[ 3 ];
			$after  = $m[ 4 ];
			$val    = preg_replace( '/&(?!amp;|lt;|gt;|quot;|apos;|#[0-9]+;|#x[0-9A-Fa-f]+;)/', '&amp;', $val );
			return $before . $val . $after;
		}, $html );
	}

	protected static function maybe_tidy_xhtml( $xhtml ) {
		if ( function_exists( 'tidy_repair_string' ) ) {
			$cfg   = [ 
				'indent'              => false,
				'wrap'                => 0,
				'output-xhtml'        => true,
				'show-body-only'      => false,
				'clean'               => true,
				'numeric-entities'    => true,
				'char-encoding'       => 'utf8',
				'new-blocklevel-tags' => 'nav,section,article,header,footer,figure,figcaption',
			];
			$fixed = tidy_repair_string( $xhtml, $cfg, 'utf8' );
			if ( is_string( $fixed ) && trim( $fixed ) !== '' )
				return $fixed;
		}
		return '';
	}

	protected static function normalize_xml_entities_for_epub( $html ) {
		// Convert &nbsp; and most named HTML entities to numeric references, keep the XML 1.0 five
		$keep = [ 'lt', 'gt', 'amp', 'apos', 'quot' ];
		$html = preg_replace_callback( '/&([a-zA-Z][a-zA-Z0-9]+);/', function ($m) use ($keep) {
			$name = $m[ 1 ];
			if ( in_array( $name, $keep, true ) ) {
				return $m[ 0 ];
			}
			$decoded = html_entity_decode( '&' . $name . ';', ENT_HTML5, 'UTF-8' );
			if ( $decoded === '&' . $name . ';' ) {
				// Unknown entity, leave as-is
				return $m[ 0 ];
			}
			if ( function_exists( 'mb_ord' ) ) {
				$code = mb_ord( $decoded, 'UTF-8' );
			} else {
				$u    = unpack( 'N', mb_convert_encoding( $decoded, 'UCS-4BE', 'UTF-8' ) );
				$code = $u ? $u[ 1 ] : ord( $decoded );
			}
			return '&#' . $code . ';';
		}, $html );
		return $html;
	}

	protected static function resolve_cover_image( WP_Post $post, $source, $opts = [] ) {
		$attachment_id = 0;
		if ( $source === 'featured' ) {
			$attachment_id = get_post_thumbnail_id( $post );
		} elseif ( $source === 'logo' ) {
			$logo_id       = get_theme_mod( 'custom_logo' );
			$attachment_id = $logo_id ? (int) $logo_id : 0;
		} elseif ( $source === 'custom' ) {
			if ( ! empty( $opts[ 'custom_cover_attachment_id' ] ) ) {
				$attachment_id = (int) $opts[ 'custom_cover_attachment_id' ];
			} elseif ( ! empty( $opts[ 'custom_cover_url' ] ) ) {
				// Try to read from a custom URL (best-effort)
				$url = esc_url_raw( $opts[ 'custom_cover_url' ] );
				if ( $url ) {
					$bits = wp_remote_get( $url );
					if ( ! is_wp_error( $bits ) && 200 === wp_remote_retrieve_response_code( $bits ) ) {
						$data     = wp_remote_retrieve_body( $bits );
						$mime     = wp_remote_retrieve_header( $bits, 'content-type' ) ?: 'image/jpeg';
						$filename = basename( parse_url( $url, PHP_URL_PATH ) );
						return [ $filename ?: 'cover.jpg', $data, $mime ];
					}
				}
			}
		}
		if ( ! $attachment_id )
			return null;
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) )
			return null;
		$data = @file_get_contents( $file );
		if ( false === $data )
			return null;
		$mime     = function_exists( 'mime_content_type' ) ? mime_content_type( $file ) : 'image/jpeg';
		$filename = basename( $file );
		return [ $filename, $data, $mime ];
	}

	// Build a simple inline HTML TOC from headings up to the given depth. Returns [newHtml, tocHtml]
	protected static function build_epub_toc_html( $html, $depth ) {
		$pattern = '#<h([1-6])(\s+[^>]*)?>(.*?)</h\1>#is';
		$matches = [];
		$toc     = [];
		$usedIds = [];
		$newHtml = $html;
		if ( preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$level = (int) $m[ 1 ];
				if ( $level > $depth )
					continue;
				$attrs = isset( $m[ 2 ] ) ? $m[ 2 ] : '';
				$text  = wp_strip_all_tags( $m[ 3 ] );
				$id    = self::slugify( $text );
				$base  = $id;
				$i     = 2;
				while ( isset( $usedIds[ $id ] ) ) {
					$id = $base . '-' . $i++;
				}
				$usedIds[ $id ] = true;
				// Insert id attribute
				$withId  = sprintf( '<h%d id="%s"%s>%s</h%d>', $level, esc_attr( $id ), $attrs, $m[ 3 ], $level );
				$newHtml = str_replace( $m[ 0 ], $withId, $newHtml );
				$toc[]   = [ 'level' => $level, 'text' => $text, 'id' => $id ];
			}
		}
		if ( empty( $toc ) )
			return [ $newHtml, '' ];
		// Flat list with level classes
		$out = '<ul class="read-offline-epub-toc">';
		foreach ( $toc as $item ) {
			$out .= sprintf( '<li class="lvl-%d"><a href="#%s">%s</a></li>', $item[ 'level' ], esc_attr( $item[ 'id' ] ), esc_html( $item[ 'text' ] ) );
		}
		$out .= '</ul>';
		return [ $newHtml, $out ];
	}

	protected static function slugify( $text ) {
		$text = strtolower( trim( $text ) );
		$text = preg_replace( '/[^a-z0-9\s-]/', '', $text );
		$text = preg_replace( '/[\s-]+/', '-', $text );
		return trim( $text, '-' );
	}

	public static function zip_files( $paths, $zip_name ) {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads[ 'basedir' ] ) . 'read-offline/';
		wp_mkdir_p( $dir );
		$zip_path = $dir . sanitize_file_name( $zip_name );

		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new ZipArchive();
			if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
				return new WP_Error( 'zip_open_failed', 'Could not create zip' );
			}
			foreach ( (array) $paths as $path ) {
				if ( file_exists( $path ) ) {
					$zip->addFile( $path, basename( $path ) );
				}
			}
			$zip->close();
			return $zip_path;
		}
		return new WP_Error( 'zip_missing', 'Zip extension not available' );
	}

	protected static function build_filename( WP_Post $post, $format, $hash ) {
		$settings = get_option( 'read_offline_settings_general', [] );
		$template = $settings[ 'filename' ] ?? '{site}-{post_slug}-{format}';
		$repl     = [ 
			'{site}'      => sanitize_title( get_bloginfo( 'name' ) ),
			'{post_slug}' => $post->post_name,
			'{post_id}'   => $post->ID,
			'{title}'     => sanitize_title( $post->post_title ),
			'{format}'    => $format,
			'{date}'      => date( 'Ymd' ),
			'{lang}'      => get_locale(),
		];
		$name     = strtr( $template, $repl );
		$name     = sanitize_file_name( $name );
		$ext      = $format === 'pdf' ? '.pdf' : '.epub';
		$ver      = substr( $hash, 0, 8 );
		return $name . '-v' . $ver . $ext;
	}

	protected static function path_to_url( $path ) {
		$uploads = wp_upload_dir();
		return str_replace( $uploads[ 'basedir' ], $uploads[ 'baseurl' ], $path );
	}

	protected static function compute_hash( WP_Post $post, $format ) {
		$general   = get_option( 'read_offline_settings_general', [] );
		$pdf       = get_option( 'read_offline_settings_pdf', [] );
		$epub      = get_option( 'read_offline_settings_epub', [] );
		$content   = apply_filters( 'the_content', $post->post_content );
		$signature = wp_json_encode( [ $post->post_modified_gmt, $format, $general, $pdf, $epub, md5( $content ) ] );
		return sha1( $signature );
	}

	public static function clear_cache() {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads[ 'basedir' ] ) . 'read-offline/';
		if ( ! file_exists( $dir ) )
			return true;
		self::rrmdir( $dir );
		return true;
	}
	protected static function rrmdir( $dir ) {
		$items = @scandir( $dir );
		if ( ! $items )
			return;
		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' )
				continue;
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) )
				self::rrmdir( $path );
			else
				@unlink( $path );
		}
		@rmdir( $dir );
	}
}
