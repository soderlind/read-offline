<?php
/**
 * Export engine for Read Offline.
 *
 * Provides REST endpoint and generation of PDF (mPDF) and EPUB (PHPePub),
 * with caching and helper utilities.
 *
 * @package Read_Offline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Read_Offline_Export {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest' ) );
	}

	/**
	 * Register the REST route.
	 *
	 * @return void
	 */
	public static function register_rest() {
		register_rest_route(
			'read-offline/v1',
			'/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_export' ),
				'args'                => array(
					'postId' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'format' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'pdf', 'epub', 'md' ),
					),
					'nonce'  => array(
						'required' => false,
						'type'     => 'string',
					),
				),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST callback to export a post.
	 *
	 * @param WP_REST_Request $req Request object.
	 * @return WP_REST_Response
	 */
	public static function rest_export( WP_REST_Request $req ) {
		$post_id = (int) $req->get_param( 'postId' );
		$format  = sanitize_key( (string) $req->get_param( 'format' ) );
		$nonce   = sanitize_text_field( wp_unslash( (string) $req->get_param( 'nonce' ) ) );

		$post = get_post( $post_id );
		if ( ! $post || ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post_id ) ) ) {
			return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
		}
		// Require nonce only for non-public content; allow published content without nonce
		if ( 'publish' !== $post->post_status ) {
			if ( ! current_user_can( 'read_post', $post_id ) && ! wp_verify_nonce( $nonce, 'read_offline_export_' . $post_id ) ) {
				return new WP_REST_Response( array( 'error' => 'forbidden' ), 403 );
			}
		}

		$path = self::generate( $post_id, $format );
		if ( is_wp_error( $path ) || ! $path ) {
			$code = is_wp_error( $path ) ? $path->get_error_code() : 'generation_failed';
			return new WP_REST_Response( array( 'error' => $code ), 500 );
		}
		$url = self::path_to_url( $path );
		return new WP_REST_Response(
			array(
				'status' => 'ok',
				'url'    => $url,
			),
			200
		);
	}

	/**
	 * Generate an export file for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $format  pdf|epub.
	 * @return string|WP_Error Absolute file path or error.
	 */
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

		if ( 'pdf' === $format ) {
			return self::generate_pdf( $post, $title, $content, $path );
		}
		if ( 'epub' === $format ) {
			return self::generate_epub( $post, $title, $content, $path );
		}
		if ( 'md' === $format ) {
			return self::generate_markdown( $post, $title, $content, $path );
		}
		return new WP_Error( 'invalid_format', 'Invalid format' );
	}

	/**
	 * Generate a single combined document from multiple posts.
	 * No long-term caching (content & settings hashed implicitly by timestamp in filename).
	 *
	 * @param array  $post_ids Array of post IDs.
	 * @param string $format   pdf|epub.
	 * @return string|WP_Error Absolute path or error.
	 */
	public static function generate_combined( $post_ids, $format ) {
		$post_ids = array_values( array_unique( array_map( 'intval', (array) $post_ids ) ) );
		$post_ids = array_filter( $post_ids, function ($pid) {
			return get_post( $pid );
		} );
		if ( empty( $post_ids ) ) {
			return new WP_Error( 'not_found', 'No valid posts provided' );
		}
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads[ 'basedir' ] ) . 'read-offline/';
		wp_mkdir_p( $dir );
		$site     = sanitize_title( get_bloginfo( 'name' ) );
		$ts       = current_time( 'Ymd_His' );
		$filename = sprintf( '%s_%s_%dposts.%s', $site, $ts, count( $post_ids ), $format );
		$path     = $dir . sanitize_file_name( $filename );
		if ( 'pdf' === $format ) {
			return self::generate_combined_pdf( $post_ids, $path );
		}
		if ( 'epub' === $format ) {
			return self::generate_combined_epub( $post_ids, $path );
		}
		if ( 'md' === $format ) {
			return self::generate_combined_markdown( $post_ids, $path );
		}
		return new WP_Error( 'invalid_format', 'Invalid format' );
	}
/**
	 * Generate Markdown file for a single post.
	 * Basic conversion: strip HTML tags after applying filters, keep headings, links, images.
	 * @param WP_Post $post Post.
	 * @param string $title Title.
	 * @param string $html HTML content.
	 * @param string $path Destination path (.md).
	 * @return string|WP_Error
	 */
	protected static function generate_markdown( WP_Post $post, $title, $html, $path ) {
		$md = self::html_to_markdown( $title, $html, array( 'include_author' => ! empty( get_option( 'read_offline_settings_general', array() )[ 'include_author' ] ) ? $post->post_author : 0, 'date' => get_the_date( '', $post ) ) );
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem; WP_Filesystem();
		$ok = false;
		if ( $wp_filesystem && method_exists( $wp_filesystem, 'put_contents' ) ) {
			$ok = $wp_filesystem->put_contents( $path, $md, FS_CHMOD_FILE );
		}
		if ( ! $ok ) {
			$ok = false !== @file_put_contents( $path, $md );
		}
		return $ok ? $path : new WP_Error( 'md_write_failed', 'Could not write markdown file' );
	}
/**
	 * Generate combined Markdown file from multiple posts.
	 * @param array $post_ids IDs.
	 * @param string $path Path.
	 * @return string|WP_Error
	 */
	protected static function generate_combined_markdown( $post_ids, $path ) {
		$parts = array();
		foreach ( $post_ids as $pid ) {
			$post = get_post( $pid ); if ( ! $post ) { continue; }
			$title = get_the_title( $post );
			$html  = apply_filters( 'the_content', $post->post_content );
			$html  = apply_filters( 'read_offline_content_html', $html, $post, 'md' );
			$parts[] = self::html_to_markdown( $title, $html, array( 'include_author' => ! empty( get_option( 'read_offline_settings_general', array() )[ 'include_author' ] ) ? $post->post_author : 0, 'date' => get_the_date( '', $post ) ) );
		}
		$md = implode( "\n\n---\n\n", $parts );
		if ( ! function_exists( 'WP_Filesystem' ) ) { require_once ABSPATH . 'wp-admin/includes/file.php'; }
		global $wp_filesystem; WP_Filesystem();
		$ok = false;
		if ( $wp_filesystem && method_exists( $wp_filesystem, 'put_contents' ) ) { $ok = $wp_filesystem->put_contents( $path, $md, FS_CHMOD_FILE ); }
		if ( ! $ok ) { $ok = false !== @file_put_contents( $path, $md ); }
		return $ok ? $path : new WP_Error( 'md_write_failed', 'Could not write markdown file' );
	}
/**
	 * Convert HTML fragment into a rough Markdown representation.
	 * This is intentionally lightweight to avoid adding large libraries.
	 * @param string $title Title.
	 * @param string $html HTML content.
	 * @param array $meta Meta (include_author => author_id|0, date => string).
	 * @return string Markdown.
	 */
	protected static function html_to_markdown( $title, $html, $meta = array() ) {
		$author_line = '';
		if ( ! empty( $meta['include_author'] ) ) {
			$author_line = '\n' . esc_html( get_the_author_meta( 'display_name', $meta['include_author'] ) );
			if ( ! empty( $meta['date'] ) ) { $author_line .= ' — ' . esc_html( $meta['date'] ); }
		}
		// Basic replacements
		$md = $html;
		// Remove scripts/styles
		$md = preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', $md );
		// Headings
		for ( $i = 6; $i >= 1; $i-- ) {
			$md = preg_replace( '#<h' . $i . '[^>]*>(.*?)</h' . $i . '>#is', str_repeat( '#', $i ) . ' $1\n\n', $md );
		}
		// Bold/italic
		$md = preg_replace( '#<(strong|b)>(.*?)</\1>#is', '**$2**', $md );
		$md = preg_replace( '#<(em|i)>(.*?)</\1>#is', '*$2*', $md );
		// Images ![alt](src)
		$md = preg_replace_callback( '#<img[^>]*>#i', function( $m ) {
			if ( preg_match( '#alt="([^"]*)"#i', $m[0], $alt ) ) { $a = $alt[1]; } else { $a = ''; }
			if ( preg_match( '#src="([^"]*)"#i', $m[0], $src ) ) { $s = $src[1]; } else { $s = ''; }
			return $s ? '![' . $a . '](' . $s . ')' : '';
		}, $md );
		// Links [text](url)
		$md = preg_replace( '#<a\s+[^>]*href="([^"]*)"[^>]*>(.*?)</a>#is', '[$2]($1)', $md );
		// Lists
		$md = preg_replace( '#<li[^>]*>(.*?)</li>#is', '- $1\n', $md );
		$md = preg_replace( '#</?(ul|ol)[^>]*>#i', "\n", $md );
		// Code blocks
		$md = preg_replace( '#<pre[^>]*><code[^>]*>(.*?)</code></pre>#is', "``````\n$1\n``````\n", $md );
		$md = preg_replace( '#<code>(.*?)</code>#is', '`$1`', $md );
		// Paragraphs / line breaks
		$md = preg_replace( '#<br\s*/?>#i', "\n", $md );
		$md = preg_replace( '#</p>#i', "\n\n", $md );
		$md = preg_replace( '#<p[^>]*>#i', '', $md );
		// Strip remaining tags
		$md = wp_strip_all_tags( $md );
		// Decode entities
		$md = html_entity_decode( $md, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		// Collapse excessive blank lines
		$md = preg_replace( "/\n{3,}/", "\n\n", $md );
		$front = '# ' . $title . $author_line . "\n\n";
		return $front . trim( $md ) . "\n";
	}

	/**
	 * Internal: build combined PDF.
	 *
	 * @param array  $post_ids IDs.
	 * @param string $path     Destination path.
	 * @return string|WP_Error
	 */
	protected static function generate_combined_pdf( $post_ids, $path ) {
		if ( ! class_exists( '\\Mpdf\\Mpdf' ) ) {
			return new WP_Error( 'mpdf_missing', 'mPDF not available. Install dependencies.' );
		}
		$pdf_opts  = get_option( 'read_offline_settings_pdf', array() );
		$gen_opts  = get_option( 'read_offline_settings_general', array() );
		$m         = $pdf_opts[ 'margins' ] ?? array( 't' => 15, 'r' => 15, 'b' => 15, 'l' => 15 );
		$size      = $pdf_opts[ 'size' ] ?? 'A4';
		$formatArg = $size;
		if ( is_string( $size ) && 'custom' === strtolower( $size ) ) {
			$custom = isset( $pdf_opts[ 'custom_size' ] ) ? trim( (string) $pdf_opts[ 'custom_size' ] ) : '';
			if ( $custom && preg_match( '/^(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)$/i', $custom, $mm ) ) {
				$w = (float) $mm[ 1 ];
				$h = (float) $mm[ 2 ];
				if ( $w > 0 && $h > 0 ) {
					$formatArg = array( $w, $h );
				}
			}
			if ( 'custom' === $formatArg ) {
				$formatArg = 'A4';
			}
		}
		try {
			$mpdf = new \Mpdf\Mpdf(
				array(
					'format'        => $formatArg,
					'margin_left'   => (int) ( $m[ 'l' ] ?? 15 ),
					'margin_right'  => (int) ( $m[ 'r' ] ?? 15 ),
					'margin_top'    => (int) ( $m[ 't' ] ?? 15 ),
					'margin_bottom' => (int) ( $m[ 'b' ] ?? 15 ),
				)
			);
			$mpdf->SetTitle( get_bloginfo( 'name' ) . ' – Combined Export' );
			$mpdf->SetAuthor( get_bloginfo( 'name' ) );
			if ( ! empty( $pdf_opts[ 'header' ] ) ) {
				$mpdf->SetHTMLHeader( $pdf_opts[ 'header' ] );
			}
			if ( ! empty( $pdf_opts[ 'footer' ] ) ) {
				$mpdf->SetHTMLFooter( $pdf_opts[ 'footer' ] );
			} elseif ( ! empty( $pdf_opts[ 'page_numbers' ] ) ) {
				$mpdf->SetFooter( '{PAGENO}/{nbpg}' );
			}
			if ( ! empty( $pdf_opts[ 'printable' ] ) ) {
				$mpdf->SetProtection( array( 'print' ) );
			} else {
				$mpdf->SetProtection( array( 'copy' ) );
			}
			if ( ! empty( $pdf_opts[ 'watermark' ] ) ) {
				$mpdf->SetWatermarkText( $pdf_opts[ 'watermark' ] );
				$mpdf->showWatermarkText = true;
			}
			// TOC
			if ( ! empty( $pdf_opts[ 'toc' ] ) ) {
				$depth = max( 1, min( 6, (int) ( $pdf_opts[ 'toc_depth' ] ?? 3 ) ) );
				$h2toc = array();
				for ( $i = 1; $i <= $depth; $i++ ) {
					$h2toc[ 'H' . $i ] = $i - 1;
				}
				$mpdf->h2toc = $h2toc;
				$mpdf->TOCpagebreakByArray( array( 'toc-preHTML' => '<h1>' . esc_html( __( 'Contents', 'read-offline' ) ) . '</h1>', 'links' => 1 ) );
			}
			$base_css         = 'img{max-width:100%;height:auto;} figure{margin:0;}';
			$base_css .= $gen_opts[ 'css' ] ?? '';
			$base_css .= apply_filters( 'read_offline_pdf_css', '', null );
			$include_author   = ! empty( $gen_opts[ 'include_author' ] );
			$include_featured = ! empty( $gen_opts[ 'include_featured' ] );
			$first            = true;
			foreach ( $post_ids as $pid ) {
				$post = get_post( $pid );
				if ( ! $post ) {
					continue;
				}
				if ( ! $first ) {
					$mpdf->AddPage();
				}
				$first      = false;
				$title      = get_the_title( $post );
				$content    = apply_filters( 'the_content', $post->post_content );
				$content    = apply_filters( 'read_offline_content_html', $content, $post, 'pdf' );
				$headerBits = '<h1>' . esc_html( $title ) . '</h1>';
				$metaBits   = '';
				if ( $include_author ) {
					$metaBits .= '<p style="font-size:12px;color:#555;">' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . ' – ' . esc_html( get_the_date( '', $post ) ) . '</p>';
				}
				if ( $include_featured && has_post_thumbnail( $post ) ) {
					$img        = get_the_post_thumbnail( $post, 'large', array( 'style' => 'max-width:100%;height:auto;margin:0 0 16px;' ) );
					$headerBits .= $img ? $img : '';
				}
				$mpdf->WriteHTML( '<style>' . $base_css . '</style>' . $headerBits . $metaBits . $content );
			}
			$mpdf->Output( $path, \Mpdf\Output\Destination::FILE );
			return $path;
		} catch (\Throwable $e) {
			return new WP_Error( 'pdf_failed', $e->getMessage() );
		}
	}

	/**
	 * Internal: build combined EPUB.
	 *
	 * @param array  $post_ids IDs.
	 * @param string $path     Destination path.
	 * @return string|WP_Error
	 */
	protected static function generate_combined_epub( $post_ids, $path ) {
		if ( ! class_exists( '\\PHPePub\\Core\\EPub' ) ) {
			return new WP_Error( 'phpepub_missing', 'PHPePub not available. Install dependencies.' );
		}
		$epub_opts        = get_option( 'read_offline_settings_epub', array() );
		$gen_opts         = get_option( 'read_offline_settings_general', array() );
		$meta             = $epub_opts[ 'meta' ] ?? array();
		$author           = $meta[ 'author' ] ?? get_bloginfo( 'name' );
		$publisher        = $meta[ 'publisher' ] ?? get_bloginfo( 'name' );
		$lang             = $meta[ 'lang' ] ?? get_locale();
		$profile          = $epub_opts[ 'css_profile' ] ?? 'light';
		$custom_css       = $epub_opts[ 'custom_css' ] ?? '';
		$include_author   = ! empty( $gen_opts[ 'include_author' ] );
		$include_featured = ! empty( $gen_opts[ 'include_featured' ] );
		$css              = '';
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
		$css = apply_filters( 'read_offline_epub_css', $css, null, $epub_opts );
		try {
			$book = new \PHPePub\Core\EPub();
			$book->setTitle( get_bloginfo( 'name' ) . ' – Combined Export' );
			$book->setIdentifier( home_url(), \PHPePub\Core\EPub::IDENTIFIER_URI );
			$book->setLanguage( $lang );
			$book->setAuthor( $author, $author );
			$book->setPublisher( $publisher, get_site_url() );
			$book->setSourceURL( home_url() );
			$cover = apply_filters( 'read_offline_epub_cover', null, null, $epub_opts );
			if ( ! $cover ) {
				// Attempt to derive from first post's featured image.
				$first = get_post( $post_ids[ 0 ] );
				if ( $first ) {
					$cover = self::resolve_cover_image( $first, $epub_opts[ 'cover' ] ?? 'featured', $epub_opts );
				}
			}
			if ( $cover && method_exists( $book, 'setCoverImage' ) ) {
				list( $fn, $bytes, $mime ) = $cover;
				$book->setCoverImage( $fn, $bytes, $mime );
			}
			$index = 1;
			foreach ( $post_ids as $pid ) {
				$post = get_post( $pid );
				if ( ! $post ) {
					continue;
				}
				$title   = get_the_title( $post );
				$content = apply_filters( 'the_content', $post->post_content );
				$content = apply_filters( 'read_offline_content_html', $content, $post, 'epub' );
				$header  = '<h1>' . esc_html( $title ) . '</h1>';
				if ( $include_featured && has_post_thumbnail( $post ) ) {
					$img    = get_the_post_thumbnail( $post, 'large', array( 'style' => 'max-width:100%;height:auto;margin:0 0 1em;' ) );
					$header .= $img ? $img : '';
				}
				if ( $include_author ) {
					$header .= '<p style="font-size:0.8em;color:#555;">' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . ' – ' . esc_html( get_the_date( '', $post ) ) . '</p>';
				}
				$body      = $header . $content;
				$xhtml     = self::wrap_epub_xhtml_document( $title, $lang, $body, $css );
				$chapterFN = 'chapter' . $index . '.xhtml';
				$book->addChapter( sanitize_title( $title ), $chapterFN, $xhtml );
				$index++;
			}
			$book->finalize();
			$dir      = dirname( $path );
			$basename = preg_replace( '/\.epub$/', '', basename( $path ) );
			$book->saveBook( $basename, $dir );
			return $path;
		} catch (\Throwable $e) {
			return new WP_Error( 'epub_failed', $e->getMessage() );
		}
	}

	/**
	 * Generate PDF using mPDF.
	 *
	 * @param WP_Post $post  Post object.
	 * @param string  $title Title.
	 * @param string  $html  HTML content.
	 * @param string  $path  Destination path.
	 * @return string|WP_Error
	 */
	protected static function generate_pdf( WP_Post $post, $title, $html, $path ) {
		$pdf_opts = get_option( 'read_offline_settings_pdf', array() );
		$gen_opts = get_option( 'read_offline_settings_general', array() );
		$size     = $pdf_opts[ 'size' ] ?? 'A4';
		$m        = $pdf_opts[ 'margins' ] ?? array(
			't' => 15,
			'r' => 15,
			'b' => 15,
			'l' => 15,
		);
		$css      = 'img{max-width:100%;height:auto;} figure{margin:0;}' . ( $gen_opts[ 'css' ] ?? '' );
		$css .= apply_filters( 'read_offline_pdf_css', '', $post );

		if ( class_exists( '\\Mpdf\\Mpdf' ) ) {
			try {
				// Support custom size like "210x297" (mm) when size is set to Custom
				$formatArg = $size;
				if ( is_string( $size ) && 'custom' === strtolower( $size ) ) {
					$custom = isset( $pdf_opts[ 'custom_size' ] ) ? trim( (string) $pdf_opts[ 'custom_size' ] ) : '';
					if ( $custom && preg_match( '/^\s*(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*$/i', $custom, $mm ) ) {
						$w = (float) $mm[ 1 ];
						$h = (float) $mm[ 2 ];
						if ( 0 < $w && 0 < $h ) {
							$formatArg = array( $w, $h );
						}
					}
					// If custom was selected but not valid, fall back to A4 to avoid mPDF errors.
					if ( 'custom' === $formatArg ) {
						$formatArg = 'A4';
					}
				}
				$mpdf = new \Mpdf\Mpdf(
					array(
						'format'        => $formatArg,
						'margin_left'   => (int) ( $m[ 'l' ] ?? 15 ),
						'margin_right'  => (int) ( $m[ 'r' ] ?? 15 ),
						'margin_top'    => (int) ( $m[ 't' ] ?? 15 ),
						'margin_bottom' => (int) ( $m[ 'b' ] ?? 15 ),
					)
				);
				$mpdf->SetTitle( $title );
				$mpdf->SetAuthor( get_bloginfo( 'name' ) );

				// Header/footer
				if ( ! empty( $pdf_opts[ 'header' ] ) ) {
					$mpdf->SetHTMLHeader( $pdf_opts[ 'header' ] );
				}
				if ( ! empty( $pdf_opts[ 'footer' ] ) ) {
					$mpdf->SetHTMLFooter( $pdf_opts[ 'footer' ] );
				} elseif ( ! empty( $pdf_opts[ 'page_numbers' ] ) ) {
					$mpdf->SetFooter( '{PAGENO}/{nbpg}' );
				}

				// Printable toggle
				if ( isset( $pdf_opts[ 'printable' ] ) && ! $pdf_opts[ 'printable' ] ) {
					$mpdf->SetProtection( array( 'copy' ) );
				} else {
					$mpdf->SetProtection( array( 'print' ) );
				}
				// Watermark
				if ( ! empty( $pdf_opts[ 'watermark' ] ) ) {
					$mpdf->SetWatermarkText( $pdf_opts[ 'watermark' ] );
					$mpdf->showWatermarkText = true;
				}

				// TOC before content
				if ( ! empty( $pdf_opts[ 'toc' ] ) ) {
					$depth = max( 1, min( 6, (int) ( $pdf_opts[ 'toc_depth' ] ?? 3 ) ) );
					$h2toc = array();
					for ( $i = 1; $i <= $depth; $i++ ) {
						$h2toc[ 'H' . $i ] = $i - 1;
					}
					$mpdf->h2toc = $h2toc;
					$mpdf->TOCpagebreakByArray(
						array(
							'toc-preHTML' => '<h1>' . esc_html( __( 'Contents', 'read-offline' ) ) . '</h1>',
							'links'       => 1,
						)
					);
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
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		WP_Filesystem();
		if ( $wp_filesystem && $wp_filesystem->put_contents( $path, $html, FS_CHMOD_FILE ) ) {
			// Saved via WP_Filesystem
		} else {
			@file_put_contents( $path, $html );
		}
		return new WP_Error( 'mpdf_missing', 'mPDF not available. Install dependencies.' );
	}

	/**
	 * Generate EPUB using PHPePub.
	 *
	 * @param WP_Post $post  Post object.
	 * @param string  $title Title.
	 * @param string  $html  HTML content.
	 * @param string  $path  Destination path.
	 * @return string|WP_Error
	 */
	protected static function generate_epub( WP_Post $post, $title, $html, $path ) {
		$epub_opts  = get_option( 'read_offline_settings_epub', array() );
		$meta       = $epub_opts[ 'meta' ] ?? array();
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
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		WP_Filesystem();
		if ( $wp_filesystem && $wp_filesystem->put_contents( $path, $html, FS_CHMOD_FILE ) ) {
			// Saved via WP_Filesystem
		} else {
			@file_put_contents( $path, $html );
		}
		return new WP_Error( 'phpepub_missing', 'PHPePub not available. Install dependencies.' );
	}

	// Build a well-formed XHTML document for EPUB content
	/**
	 * Wrap an XHTML 1.1 document around body content suitable for EPUB.
	 *
	 * @param string $title    Title.
	 * @param string $lang     Language tag.
	 * @param string $bodyHtml Body inner HTML.
	 * @param string $css      Optional CSS.
	 * @return string Well-formed XHTML string.
	 */
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
	/**
	 * Ensure attributes on void elements are quoted per XHTML rules.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	protected static function normalize_xhtml_attributes_quoted( $html ) {
		$tags    = array( 'img', 'source', 'track', 'input', 'meta', 'link', 'area', 'base', 'col', 'embed', 'param', 'br', 'hr', 'wbr' );
		$pattern = '/<(' . implode( '|', $tags ) . ')\b([^>]*?)\/?>(?!\s*<\/\1>)/i';
		return preg_replace_callback(
			$pattern,
			function ($m) {
				$tag   = strtolower( $m[ 1 ] );
				$attrs = trim( $m[ 2 ] );
				if ( '' === $attrs ) {
					return '<' . $tag . ' />';
				}
				$rebuilt   = array();
				$regexAttr = '/([:\w-]+)(?:\s*=\\s*(?:"([^"]*)"|\'([^\']*)\'|([^\\s"\'<>/]+)))?/';
				if ( preg_match_all( $regexAttr, $attrs, $am, PREG_SET_ORDER ) ) {
					foreach ( $am as $a ) {
						$name  = strtolower( $a[ 1 ] );
						$value = '';
						if ( isset( $a[ 3 ] ) && '' !== $a[ 3 ] ) {
							$value = $a[ 3 ];
						} elseif ( isset( $a[ 4 ] ) && '' !== $a[ 4 ] ) {
							$value = $a[ 4 ];
						} elseif ( isset( $a[ 5 ] ) && '' !== $a[ 5 ] ) {
							$value = $a[ 5 ];
						}
						// XHTML boolean attributes must be name="name"
						if ( '' === $value ) {
							$value = $name;
						}
						$value     = str_replace( '"', '&quot;', $value );
						$rebuilt[] = $name . '="' . $value . '"';
					}
				}
				return '<' . $tag . ' ' . implode( ' ', $rebuilt ) . ' />';
			},
			$html
		);
	}

	// Try to repair malformed HTML fragments using DOMDocument, returning a cleaner HTML string
	/**
	 * Attempt to repair a malformed HTML fragment using DOMDocument.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
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
		return '' !== $out ? $out : $html;
	}

	/**
	 * Normalize XHTML void elements to self-closing.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	protected static function normalize_xhtml_void_elements( $html ) {
		$tags = array( 'img', 'br', 'hr', 'input', 'meta', 'link', 'source', 'track', 'wbr', 'area', 'base', 'col', 'embed', 'param' );
		foreach ( $tags as $t ) {
			$pattern = '/<' . $t . '\b((?>[^"\'<>]|"[^"]*"|\'[^\']*\')*)>/i';
			$html    = preg_replace_callback(
				$pattern,
				function ($m) use ($t) {
					$attrs = rtrim( $m[ 1 ] );
					// Already self-closed? (<tag .../>)
					if ( preg_match( '/\/$/', $attrs ) ) {
						$attrs = rtrim( substr( $attrs, 0, -1 ) );
						$space = $attrs !== '' ? ' ' : '';
						return '<' . strtolower( $t ) . $space . $attrs . ' />';
					}
					$space = '' !== $attrs ? ' ' : '';
					return '<' . strtolower( $t ) . $space . $attrs . ' />';
				},
				$html
			);
			// Remove any erroneous closing tags for void elements: </tag>
			$html = preg_replace( '/<\/' . $t . '\s*>/i', '', $html );
		}
		return $html;
	}

	/**
	 * Replace raw ampersands in attribute values with &amp; unless they are valid entities.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	protected static function normalize_xml_ampersands_in_attributes( $html ) {
		// Replace raw & in attribute values with &amp; unless already an entity
		return preg_replace_callback(
			'/(\w+)=("|")(.*?)(\2)/s',
			function ($m) {
				$before = $m[ 1 ] . '=' . $m[ 2 ];
				$val    = $m[ 3 ];
				$after  = $m[ 4 ];
				$val    = preg_replace( '/&(?!amp;|lt;|gt;|quot;|apos;|#[0-9]+;|#x[0-9A-Fa-f]+;)/', '&amp;', $val );
				return $before . $val . $after;
			},
			$html
		);
	}

	/**
	 * Run Tidy if available to normalize XHTML.
	 *
	 * @param string $xhtml XHTML content.
	 * @return string Empty string if Tidy unavailable or failed, otherwise fixed content.
	 */
	protected static function maybe_tidy_xhtml( $xhtml ) {
		if ( function_exists( 'tidy_repair_string' ) ) {
			$cfg   = array(
				'indent'              => false,
				'wrap'                => 0,
				'output-xhtml'        => true,
				'show-body-only'      => false,
				'clean'               => true,
				'numeric-entities'    => true,
				'char-encoding'       => 'utf8',
				'new-blocklevel-tags' => 'nav,section,article,header,footer,figure,figcaption',
			);
			$fixed = tidy_repair_string( $xhtml, $cfg, 'utf8' );
			if ( is_string( $fixed ) && '' !== trim( $fixed ) ) {
				return $fixed;
			}
		}
		return '';
	}

	/**
	 * Convert named HTML entities to numeric references for EPUB safety.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	protected static function normalize_xml_entities_for_epub( $html ) {
		// Convert &nbsp; and most named HTML entities to numeric references, keep the XML 1.0 five
		$keep = array( 'lt', 'gt', 'amp', 'apos', 'quot' );
		$html = preg_replace_callback(
			'/&([a-zA-Z][a-zA-Z0-9]+);/',
			function ($m) use ($keep) {
				$name = $m[ 1 ];
				if ( in_array( $name, $keep, true ) ) {
					return $m[ 0 ];
				}
				$decoded = html_entity_decode( '&' . $name . ';', ENT_HTML5, 'UTF-8' );
				if ( '&' . $name . ';' === $decoded ) {
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
			},
			$html
		);
		return $html;
	}

	/**
	 * Resolve cover image bytes from featured image, site logo, or custom.
	 *
	 * @param WP_Post $post   Post.
	 * @param string  $source featured|logo|custom.
	 * @param array   $opts   EPUB options.
	 * @return array|null [filename, bytes, mime] or null
	 */
	protected static function resolve_cover_image( WP_Post $post, $source, $opts = array() ) {
		$attachment_id = 0;
		if ( 'featured' === $source ) {
			$attachment_id = get_post_thumbnail_id( $post );
		} elseif ( 'logo' === $source ) {
			$logo_id       = get_theme_mod( 'custom_logo' );
			$attachment_id = $logo_id ? (int) $logo_id : 0;
		} elseif ( 'custom' === $source ) {
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
						return array( $filename ?: 'cover.jpg', $data, $mime );
					}
				}
			}
		}
		if ( ! $attachment_id ) {
			return null;
		}
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return null;
		}
		// Prefer WP_Filesystem if available
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		WP_Filesystem();
		$data = null;
		if ( $wp_filesystem && method_exists( $wp_filesystem, 'get_contents' ) ) {
			$data = $wp_filesystem->get_contents( $file );
		} else {
			$data = @file_get_contents( $file );
		}
		if ( false === $data || null === $data ) {
			return null;
		}
		$mime     = function_exists( 'mime_content_type' ) ? mime_content_type( $file ) : 'image/jpeg';
		$filename = basename( $file );
		return array( $filename, $data, $mime );
	}

	// Build a simple inline HTML TOC from headings up to the given depth. Returns [newHtml, tocHtml]
	/**
	 * Build a simple inline HTML TOC from headings up to the given depth.
	 *
	 * @param string $html  HTML.
	 * @param int    $depth Depth 1-6.
	 * @return array{0:string,1:string}
	 */
	protected static function build_epub_toc_html( $html, $depth ) {
		$pattern = '#<h([1-6])(\s+[^>]*)?>(.*?)</h\1>#is';
		$matches = array();
		$toc     = array();
		$usedIds = array();
		$newHtml = $html;
		if ( preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$level = (int) $m[ 1 ];
				if ( $level > $depth ) {
					continue;
				}
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
				$toc[]   = array(
					'level' => $level,
					'text'  => $text,
					'id'    => $id,
				);
			}
		}
		if ( empty( $toc ) ) {
			return array( $newHtml, '' );
		}
		// Flat list with level classes
		$out = '<ul class="read-offline-epub-toc">';
		foreach ( $toc as $item ) {
			$out .= sprintf( '<li class="lvl-%d"><a href="#%s">%s</a></li>', $item[ 'level' ], esc_attr( $item[ 'id' ] ), esc_html( $item[ 'text' ] ) );
		}
		$out .= '</ul>';
		return array( $newHtml, $out );
	}

	/**
	 * Make a simple slug from text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	protected static function slugify( $text ) {
		$text = strtolower( trim( $text ) );
		$text = preg_replace( '/[^a-z0-9\s-]/', '', $text );
		$text = preg_replace( '/[\s-]+/', '-', $text );
		return trim( $text, '-' );
	}

	/**
	 * Create a ZIP from file paths.
	 *
	 * @param array  $paths    List of absolute file paths.
	 * @param string $zip_name Desired ZIP filename.
	 * @return string|WP_Error Absolute path or error.
	 */
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

	/**
	 * Build a cache filename based on template and short hash.
	 *
	 * @param WP_Post $post   Post.
	 * @param string  $format Format.
	 * @param string  $hash   Hash.
	 * @return string
	 */
	protected static function build_filename( WP_Post $post, $format, $hash ) {
		$settings = get_option( 'read_offline_settings_general', array() );
		$template = $settings[ 'filename' ] ?? '{site}-{post_slug}-{format}';
		$repl     = array(
			'{site}'      => sanitize_title( get_bloginfo( 'name' ) ),
			'{post_slug}' => $post->post_name,
			'{post_id}'   => $post->ID,
			'{title}'     => sanitize_title( $post->post_title ),
			'{format}'    => $format,
			'{date}'      => date( 'Ymd' ),
			'{lang}'      => get_locale(),
		);
		$name     = strtr( $template, $repl );
		$name     = sanitize_file_name( $name );
		if ( 'pdf' === $format ) { $ext = '.pdf'; } elseif ( 'epub' === $format ) { $ext = '.epub'; } elseif ( 'md' === $format ) { $ext = '.md'; } else { $ext = '.' . preg_replace( '/[^a-z0-9]/', '', $format ); }
		$ver      = substr( $hash, 0, 8 );
		return $name . '-v' . $ver . $ext;
	}

	/**
	 * Convert cache path to URL in uploads.
	 *
	 * @param string $path Absolute path.
	 * @return string URL.
	 */
	protected static function path_to_url( $path ) {
		$uploads = wp_upload_dir();
		return str_replace( $uploads[ 'basedir' ], $uploads[ 'baseurl' ], $path );
	}

	/**
	 * Compute a content/settings hash for cache versioning.
	 *
	 * @param WP_Post $post   Post.
	 * @param string  $format Format.
	 * @return string SHA1 hash.
	 */
	protected static function compute_hash( WP_Post $post, $format ) {
		$general   = get_option( 'read_offline_settings_general', array() );
		$pdf       = get_option( 'read_offline_settings_pdf', array() );
		$epub      = get_option( 'read_offline_settings_epub', array() );
		$content   = apply_filters( 'the_content', $post->post_content );
		$signature = wp_json_encode( array( $post->post_modified_gmt, $format, $general, $pdf, $epub, md5( $content ) ) );
		return sha1( $signature );
	}

	/**
	 * Clear the plugin's uploads cache directory.
	 *
	 * @return bool
	 */
	public static function clear_cache() {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads[ 'basedir' ] ) . 'read-offline/';
		if ( ! file_exists( $dir ) ) {
			return true;
		}
		self::rrmdir( $dir );
		return true;
	}
	/**
	 * Recursively delete a directory, preferring WP_Filesystem if available.
	 *
	 * @param string $dir Directory absolute path.
	 * @return void
	 */
	protected static function rrmdir( $dir ) {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		WP_Filesystem();
		if ( $wp_filesystem && method_exists( $wp_filesystem, 'rmdir' ) ) {
			$wp_filesystem->rmdir( $dir, true );
			return;
		}
		$items = @scandir( $dir );
		if ( ! $items ) {
			return;
		}
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				self::rrmdir( $path );
			} else {
				@unlink( $path );
			}
		}
		@rmdir( $dir );
	}
}
