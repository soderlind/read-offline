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
	 * Format -> handler map for single & combined generation.
	 * @var array<string,array{single:string,combined:string}>
	 */
	protected static $format_handlers = array(
		'pdf'  => array( 'single' => 'generate_pdf', 'combined' => 'generate_combined_pdf' ),
		'epub' => array( 'single' => 'generate_epub', 'combined' => 'generate_combined_epub' ),
		'md'   => array( 'single' => 'generate_markdown', 'combined' => 'generate_combined_markdown' ),
	);
	/**
	 * Per-request rate limiting context.
	 *
	 * @var array{limit?:int,remaining?:int,reset?:int}
	 */
	private static $rate_context = array();
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest' ) );
		add_filter( 'rest_request_dispatch', array( __CLASS__, 'add_rate_limit_headers' ), 10, 3 );
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
				'permission_callback' => array( __CLASS__, 'rest_permission' ),
			)
		);
	}

	/**
	 * Permission callback enforcing public toggle and simple rate limiting.
	 *
	 * @return bool|WP_Error
	 */
	public static function rest_permission( $request ) {
		// Reset per-request context.
		self::$rate_context = array();
		$options            = get_option( 'read_offline_settings_general', array() );
		$public             = ! empty( $options[ 'rest_public' ] );
		if ( ! $public && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', __( 'REST export disabled publicly.', 'read-offline' ), array( 'status' => 403 ) );
		}
		if ( $public && ! is_user_logged_in() ) {
			$limit  = isset( $options[ 'rest_rate_limit' ] ) ? (int) $options[ 'rest_rate_limit' ] : 10;
			$window = isset( $options[ 'rest_rate_window' ] ) ? (int) $options[ 'rest_rate_window' ] : 60;
			if ( $limit > 0 && $window > 0 ) {
				$ip     = isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ 'REMOTE_ADDR' ] ) ) : 'unknown';
				$key    = 'ro_rl_' . md5( $ip );
				$record = wp_cache_get( $key, 'read_offline' );
				if ( false === $record ) {
					$record = get_transient( $key );
				}
				$now = time();
				if ( ! is_array( $record ) || $record[ 'expires' ] <= $now ) {
					$record = array( 'count' => 0, 'expires' => $now + $window );
				}
				if ( $record[ 'count' ] >= $limit ) {
					$retry              = max( 1, $record[ 'expires' ] - $now );
					self::$rate_context = array(
						'limit'     => $limit,
						'remaining' => 0,
						'reset'     => $record[ 'expires' ],
					);
					return new WP_Error( 'rate_limited', sprintf( __( 'Rate limit exceeded. Retry in %d seconds.', 'read-offline' ), $retry ), array( 'status' => 429, 'retry_after' => $retry ) );
				}
				$record[ 'count' ]++;
				wp_cache_set( $key, $record, 'read_offline', $record[ 'expires' ] - $now );
				set_transient( $key, $record, $record[ 'expires' ] - $now );
				self::$rate_context = array(
					'limit'     => $limit,
					'remaining' => max( 0, $limit - $record[ 'count' ] ),
					'reset'     => $record[ 'expires' ],
				);
			}
		}
		return true;
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
	 * Filter REST dispatch errors to append Retry-After header when rate limited.
	 */
	public static function add_rate_limit_headers( $response, $handler, $request ) {
		$ctx = self::$rate_context;
		if ( is_wp_error( $response ) && 'rate_limited' === $response->get_error_code() ) {
			$retry = (int) $response->get_error_data( 'retry_after' );
			if ( $retry > 0 ) {
				$rest_response = new WP_REST_Response( array( 'error' => 'rate_limited', 'retry_after' => $retry ) );
				$rest_response->set_status( 429 );
				$rest_response->header( 'Retry-After', (string) $retry );
				if ( ! empty( $ctx ) ) {
					$rest_response->header( 'X-RateLimit-Limit', (string) $ctx[ 'limit' ] );
					$rest_response->header( 'X-RateLimit-Remaining', (string) $ctx[ 'remaining' ] );
					$rest_response->header( 'X-RateLimit-Reset', (string) $ctx[ 'reset' ] );
				} else {
					$rest_response->header( 'X-RateLimit-Remaining', '0' );
				}
				return $rest_response;
			}
		}
		if ( ! is_wp_error( $response ) && ! empty( $ctx ) ) {
			if ( $response instanceof WP_REST_Response ) {
				$response->header( 'X-RateLimit-Limit', (string) $ctx[ 'limit' ] );
				$response->header( 'X-RateLimit-Remaining', (string) $ctx[ 'remaining' ] );
				$response->header( 'X-RateLimit-Reset', (string) $ctx[ 'reset' ] );
			} elseif ( is_array( $response ) ) {
				$rest_response = new WP_REST_Response( $response );
				$rest_response->header( 'X-RateLimit-Limit', (string) $ctx[ 'limit' ] );
				$rest_response->header( 'X-RateLimit-Remaining', (string) $ctx[ 'remaining' ] );
				$rest_response->header( 'X-RateLimit-Reset', (string) $ctx[ 'reset' ] );
				return $rest_response;
			}
		}
		return $response;
	}
	/**
	 * Generate an export file for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $format  pdf|epub.
	 * @return string|WP_Error Absolute file path or error.
	 */
	public static function generate( $post_id, $format ) {
		$format = strtolower( (string) $format );
		if ( empty( self::$format_handlers[ $format ] ) ) {
			return new WP_Error( 'invalid_format', 'Invalid format' );
		}
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
			return $path; // cached
		}
		$title   = get_the_title( $post );
		$content = apply_filters( 'the_content', $post->post_content );
		$content = apply_filters( 'read_offline_content_html', $content, $post, $format );
		$method  = self::$format_handlers[ $format ][ 'single' ];
		return self::$method( $post, $title, $content, $path );
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
		$format = strtolower( (string) $format );
		if ( empty( self::$format_handlers[ $format ] ) ) {
			return new WP_Error( 'invalid_format', 'Invalid format' );
		}
		$post_ids = array_values( array_unique( array_map( 'intval', (array) $post_ids ) ) );
		$post_ids = array_filter( $post_ids, function ($pid) {
			return get_post( $pid ); } );
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
		$method   = self::$format_handlers[ $format ][ 'combined' ];
		return self::$method( $post_ids, $path );
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
		$include_author = ! empty( get_option( 'read_offline_settings_general', array() )[ 'include_author' ] );
		$md             = self::html_to_markdown( $title, $html, array( 'include_author' => $include_author ? $post->post_author : 0, 'date' => get_the_date( '', $post ) ) );
		return self::write_file( $path, $md ) ? $path : new WP_Error( 'md_write_failed', 'Could not write markdown file' );
	}

	/**
	 * Generate combined Markdown file from multiple posts.
	 * @param array $post_ids IDs.
	 * @param string $path Path.
	 * @return string|WP_Error
	 */
	protected static function generate_combined_markdown( $post_ids, $path ) {
		$include_author = ! empty( get_option( 'read_offline_settings_general', array() )[ 'include_author' ] );
		$parts          = array();
		foreach ( $post_ids as $pid ) {
			$post = get_post( $pid );
			if ( ! $post ) {
				continue;
			}
			$title   = get_the_title( $post );
			$html    = apply_filters( 'the_content', $post->post_content );
			$html    = apply_filters( 'read_offline_content_html', $html, $post, 'md' );
			$parts[] = self::html_to_markdown( $title, $html, array( 'include_author' => $include_author ? $post->post_author : 0, 'date' => get_the_date( '', $post ) ) );
		}
		$md = implode( "\n\n---\n\n", $parts );
		return self::write_file( $path, $md ) ? $path : new WP_Error( 'md_write_failed', 'Could not write markdown file' );
	}

	/**
	 * Convert filtered HTML into a simple Markdown representation.
	 * NOTE: This is a lightweight, heuristic conversion – not a full HTML->MD parser.
	 * Filters: read_offline_markdown_pre / read_offline_markdown_post
	 * @param string $title Document title (used to prepend as H1 if not present).
	 * @param string $html  HTML markup.
	 * @param array  $args  { include_author:int user_id, date:string }
	 * @return string Markdown
	 */
	protected static function html_to_markdown( $title, $html, $args = array() ) {
		$pre = apply_filters( 'read_offline_markdown_pre', null, $title, $html, $args );
		if ( null !== $pre ) {
			return (string) $pre;
		}
		// Basic normalisation.
		$md = $html;
		// Protect code blocks: convert <pre><code> ... </code></pre> to fenced blocks.
		$md = preg_replace_callback( '#<pre[^>]*><code[^>]*>([\s\S]*?)</code></pre>#i', function ($m) {
			$code = html_entity_decode( $m[ 1 ], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$code = preg_replace( "/\r?\n$/", '', $code );
			return "\n```\n" . trim( $code ) . "\n```\n";
		}, $md );
		// Headings h1-h6.
		for ( $i = 6; $i >= 1; $i-- ) {
			$md = preg_replace( '#<h' . $i . '[^>]*>(.*?)</h' . $i . '>#is', function ($m) use ($i) {
				$text = trim( wp_strip_all_tags( $m[ 1 ] ) );
				return "\n" . str_repeat( '#', $i ) . ' ' . $text . "\n\n";
			}, $md );
		}
		// Bold / italics.
		$md = preg_replace( '#<(strong|b)[^>]*>(.*?)</\1>#is', '**$2**', $md );
		$md = preg_replace( '#<(em|i)[^>]*>(.*?)</\1>#is', '*$2*', $md );
		// Links.
		$md = preg_replace_callback( '#<a\s+[^>]*href=("|\')(.*?)\1[^>]*>(.*?)</a>#is', function ($m) {
			$text = trim( wp_strip_all_tags( $m[ 3 ] ) );
			$url = trim( $m[ 2 ] );
			return '[' . $text . '](' . $url . ')';
		}, $md );
		// Images.
		$md = preg_replace_callback( '#<img\s+[^>]*src=("|\')(.*?)\1[^>]*>#i', function ($m) {
			$alt = '';
			if ( preg_match( '#alt=("|\')(.*?)\1#i', $m[ 0 ], $am ) ) {
				$alt = $am[ 2 ];
			}
			return '![' . $alt . '](' . $m[ 2 ] . ')';
		}, $md );
		// Unordered lists.
		$md = preg_replace_callback( '#<ul[^>]*>([\s\S]*?)</ul>#i', function ($m) {
			$items = preg_replace( '#<li[^>]*>([\s\S]*?)</li>#i', function ($iMatch) {
				return '* ' . trim( wp_strip_all_tags( $iMatch[ 1 ] ) ) . "\n";
			}, $m[ 1 ] );
			return "\n" . trim( $items ) . "\n";
		}, $md );
		// Ordered lists.
		$listIndex = 0;
		$md        = preg_replace_callback( '#<ol[^>]*>([\s\S]*?)</ol>#i', function ($m) use (&$listIndex) {
			$listIndex = 0;
			$items = preg_replace_callback( '#<li[^>]*>([\s\S]*?)</li>#i', function ($iMatch) use (&$listIndex) {
				$listIndex++;
				return $listIndex . '. ' . trim( wp_strip_all_tags( $iMatch[ 1 ] ) ) . "\n";
			}, $m[ 1 ] );
			return "\n" . trim( $items ) . "\n";
		}, $md );
		// Blockquotes.
		$md = preg_replace_callback( '#<blockquote[^>]*>([\s\S]*?)</blockquote>#i', function ($m) {
			$text = trim( wp_strip_all_tags( $m[ 1 ] ) );
			$text = preg_replace( '/^/m', '> ', $text );
			return "\n" . $text . "\n";
		}, $md );
		// Line breaks & paragraphs.
		$md = preg_replace( '#<br\s*/?>#i', "\n", $md );
		$md = preg_replace( '#</p>#i', "\n\n", $md );
		$md = preg_replace( '#<p[^>]*>#i', '', $md );
		// Strip remaining tags.
		$md = wp_strip_all_tags( $md );
		// Decode entities.
		$md = html_entity_decode( $md, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$md = preg_replace( "/\n{3,}/", "\n\n", $md );
		$md = trim( $md );
		// Prepend title if not already first line heading.
		if ( $title && ! preg_match( '/^# /', $md ) ) {
			$md = '# ' . trim( wp_strip_all_tags( $title ) ) . "\n\n" . $md;
		}
		if ( ! empty( $args[ 'include_author' ] ) ) {
			$author = get_the_author_meta( 'display_name', (int) $args[ 'include_author' ] );
			$date   = isset( $args[ 'date' ] ) ? $args[ 'date' ] : '';
			$meta   = trim( $author . ( $date ? ' – ' . $date : '' ) );
			if ( $meta ) {
				$md .= "\n\n_" . $meta . '_';
			}
		}
		return apply_filters( 'read_offline_markdown_post', $md, $title, $html, $args );
	}

	/**
	 * Build TOC HTML for EPUB: returns array( updatedHtml, tocHtml )
	 * Adds IDs to headings if missing.
	 * @param string $html
	 * @param int $depth Max heading level.
	 * @return array
	 */
	protected static function build_epub_toc_html( $html, $depth = 3 ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$loaded = $dom->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		if ( ! $loaded ) {
			return array( $html, '' );
		}
		$xpath  = new DOMXPath( $dom );
		$nodes  = $xpath->query( '//h1 | //h2 | //h3 | //h4 | //h5 | //h6' );
		$toc    = array();
		$count  = 0;
		$maxTag = 'h' . (int) $depth;
		foreach ( $nodes as $n ) {
			if ( strcasecmp( $n->nodeName, $maxTag ) > 0 ) {
				continue; // deeper than allowed depth.
			}
			$count++;
			$id = $n->getAttribute( 'id' );
			if ( ! $id ) {
				$id = 'toc-' . $count;
				$n->setAttribute( 'id', $id );
			}
			$level = (int) substr( $n->nodeName, 1 );
			$text  = trim( preg_replace( '/\s+/', ' ', $n->textContent ) );
			$toc[] = array( 'level' => $level, 'id' => $id, 'text' => $text );
		}
		if ( empty( $toc ) ) {
			return array( $html, '' );
		}
		// Build nested list.
		$out   = '';
		$stack = array();
		$prev  = 0;
		foreach ( $toc as $entry ) {
			$l = $entry[ 'level' ];
			if ( $prev === 0 ) {
				$out .= '<ul>';
				$stack[] = 'ul';
			} elseif ( $l > $prev ) {
				$out .= '<ul>';
				$stack[] = 'ul';
			} elseif ( $l < $prev ) {
				while ( ! empty( $stack ) && $l < $prev ) {
					$out .= '</ul>';
					array_pop( $stack );
					$prev--;
				}
			}
			$out .= '<li><a href="#' . esc_attr( $entry[ 'id' ] ) . '">' . esc_html( $entry[ 'text' ] ) . '</a></li>';
			$prev = $l;
		}
		while ( ! empty( $stack ) ) {
			$out .= '</ul>';
			array_pop( $stack );
		}
		$bodyNode = $xpath->query( '//body' )->item( 0 );
		$newHtml  = '';
		if ( $bodyNode ) {
			foreach ( $bodyNode->childNodes as $child ) {
				$newHtml .= $dom->saveHTML( $child );
			}
		}
		return array( $newHtml, $out );
	}

	/* EPUB helpers for DRY */
	protected static function epub_get_meta( $epub_opts ) {
		$meta = $epub_opts[ 'meta' ] ?? array();
		return array(
			'author'    => $meta[ 'author' ] ?? get_bloginfo( 'name' ),
			'publisher' => $meta[ 'publisher' ] ?? get_bloginfo( 'name' ),
			'lang'      => $meta[ 'lang' ] ?? get_locale(),
		);
	}
	protected static function epub_css_profile( $epub_opts, $post = null ) {
		$profile    = $epub_opts[ 'css_profile' ] ?? 'light';
		$custom_css = $epub_opts[ 'custom_css' ] ?? '';
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
				break;
		}
		return apply_filters( 'read_offline_epub_css', $css, $post, $epub_opts );
	}
	protected static function epub_build_toc_and_body( $html, $epub_opts, $post_or_null, $lang ) {
		$prefixToc = '';
		if ( ! empty( $epub_opts[ 'toc' ] ) ) {
			$depth                  = max( 1, min( 6, (int) ( $epub_opts[ 'toc_depth' ] ?? 3 ) ) );
			list( $html, $tocHtml ) = self::build_epub_toc_html( $html, $depth );
			if ( $tocHtml ) {
				$tocHtml   = apply_filters( 'read_offline_epub_toc_html', $tocHtml, $post_or_null, $depth );
				$prefixToc = '<div class="read-offline-epub-toc-wrap"><h1>' . esc_html( __( 'Contents', 'read-offline' ) ) . '</h1>' . $tocHtml . '</div>';
			}
		}
		return $prefixToc . $html;
	}

	/* ================= Additional Core Helpers (restored) ================= */
	protected static function compute_hash( $post, $format ) {
		$general = get_option( 'read_offline_settings_general', array() );
		$epub    = ( 'epub' === $format ) ? get_option( 'read_offline_settings_epub', array() ) : array();
		$pdf     = ( 'pdf' === $format ) ? get_option( 'read_offline_settings_pdf', array() ) : array();
		$payload = array(
			$post->ID,
			$post->post_modified_gmt,
			$format,
			md5( wp_json_encode( $general ) ),
			md5( wp_json_encode( $epub ) ),
			md5( wp_json_encode( $pdf ) ),
		);
		return substr( md5( implode( '|', $payload ) ), 0, 12 );
	}

	protected static function build_filename( $post, $format, $hash ) {
		$slug = sanitize_title( get_the_title( $post ) );
		return $slug . '-' . $hash . '.' . $format;
	}

	protected static function path_to_url( $path ) {
		$uploads = wp_upload_dir();
		$base    = trailingslashit( $uploads[ 'basedir' ] );
		$baseurl = trailingslashit( $uploads[ 'baseurl' ] );
		if ( str_starts_with( $path, $base ) ) {
			return $baseurl . ltrim( substr( $path, strlen( $base ) ), '/' );
		}
		return $path;
	}

	protected static function resolve_cover_image( $post, $mode, $opts = array() ) {
		$bytes = $mime = $filename = null;
		if ( 'featured' === $mode && has_post_thumbnail( $post ) ) {
			$id  = get_post_thumbnail_id( $post );
			$src = wp_get_attachment_image_src( $id, 'large' );
			if ( $src ) {
				$filename = basename( parse_url( $src[ 0 ], PHP_URL_PATH ) );
				$bytes    = wp_remote_retrieve_body( wp_remote_get( $src[ 0 ] ) );
				$mime     = get_post_mime_type( $id );
			}
		} elseif ( 'first' === $mode ) {
			if ( preg_match( '#<img[^>]+src=("|\')(.*?)\1#i', $post->post_content, $m ) ) {
				$src      = $m[ 2 ];
				$filename = basename( parse_url( $src, PHP_URL_PATH ) );
				$bytes    = wp_remote_retrieve_body( wp_remote_get( $src ) );
				$mime     = wp_check_filetype( $filename )[ 'type' ] ?? 'image/jpeg';
			}
		}
		if ( $bytes && $filename ) {
			return array( $filename, $bytes, $mime ?: 'image/jpeg' );
		}
		return null;
	}

	protected static function wrap_epub_xhtml_document( $title, $lang, $body_html, $css ) {
		return '<?xml version="1.0" encoding="UTF-8"?>' .
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' .
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . esc_attr( $lang ) . '"><head><meta charset="utf-8" />' .
			'<title>' . esc_html( $title ) . '</title><style type="text/css">' . $css . '</style></head><body>' . $body_html . '</body></html>';
	}

	/* ================= PDF Generation ================= */
	protected static function generate_pdf( WP_Post $post, $title, $html, $path ) {
		$pdf_opts = get_option( 'read_offline_settings_pdf', array() );
		$gen_opts = get_option( 'read_offline_settings_general', array() );
		$mpdf     = self::build_mpdf_instance( $pdf_opts, $gen_opts, $title, $post );
		if ( is_wp_error( $mpdf ) ) {
			return $mpdf;
		}
		$css = self::assemble_pdf_css( $pdf_opts, $gen_opts, $post );
		$mpdf->WriteHTML( '<style>' . $css . '</style>', \Mpdf\HTMLParserMode::HEADER_CSS );
		$mpdf->WriteHTML( $html, \Mpdf\HTMLParserMode::HTML_BODY );
		try {
			$mpdf->Output( $path, \Mpdf\Output\Destination::FILE );
			return $path;
		} catch (\Throwable $e) {
			return new WP_Error( 'pdf_failed', $e->getMessage() );
		}
	}

	protected static function generate_combined_pdf( $post_ids, $path ) {
		$pdf_opts = get_option( 'read_offline_settings_pdf', array() );
		$gen_opts = get_option( 'read_offline_settings_general', array() );
		$dummy    = (object) array( 'post_title' => get_bloginfo( 'name' ) . ' – Combined Export' );
		$mpdf     = self::build_mpdf_instance( $pdf_opts, $gen_opts, $dummy->post_title, null );
		if ( is_wp_error( $mpdf ) ) {
			return $mpdf;
		}
		$css = self::assemble_pdf_css( $pdf_opts, $gen_opts, null );
		$mpdf->WriteHTML( '<style>' . $css . '</style>', \Mpdf\HTMLParserMode::HEADER_CSS );
		$include_author   = ! empty( $gen_opts[ 'include_author' ] );
		$include_featured = ! empty( $gen_opts[ 'include_featured' ] );
		foreach ( $post_ids as $pid ) {
			$post = get_post( $pid );
			if ( ! $post ) {
				continue;
			}
			$title   = get_the_title( $post );
			$content = apply_filters( 'the_content', $post->post_content );
			$content = apply_filters( 'read_offline_content_html', $content, $post, 'pdf' );
			$header  = self::build_post_header_html( $post, $include_featured, $include_author );
			$mpdf->WriteHTML( '<pagebreak />' . $header . $content, \Mpdf\HTMLParserMode::HTML_BODY );
		}
		try {
			$mpdf->Output( $path, \Mpdf\Output\Destination::FILE );
			return $path;
		} catch (\Throwable $e) {
			return new WP_Error( 'pdf_failed', $e->getMessage() );
		}
	}

	/* Smoke test method (manual invocation) */
	public static function debug_smoke_capabilities() {
		if ( ! function_exists( 'get_option' ) ) {
			return array( 'wp_loaded' => false );
		}
		return array(
			'wp_loaded'      => true,
			'mpdf_available' => class_exists( '\\Mpdf\\Mpdf' ),
			'epub_available' => class_exists( '\\PHPePub\\Core\\EPub' ),
		);
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

	// Add DRY helper methods.
	/** Ensure WP_Filesystem initialized. */
	protected static function ensure_filesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		WP_Filesystem();
		return $wp_filesystem;
	}

	/** Generic write file helper with WP_Filesystem fallback. */
	protected static function write_file( $path, $content, $binary = false ) {
		$wp_filesystem = self::ensure_filesystem();
		$mode          = $binary ? FS_CHMOD_FILE : FS_CHMOD_FILE; // same, semantic flag.
		if ( $wp_filesystem && method_exists( $wp_filesystem, 'put_contents' ) ) {
			if ( $wp_filesystem->put_contents( $path, $content, $mode ) ) {
				return true;
			}
		}
		return false !== @file_put_contents( $path, $content );
	}

	/** Parse PDF size option into mPDF format argument (supports custom WxH mm). */
	protected static function parse_pdf_format_arg( $pdf_opts ) {
		$size = $pdf_opts[ 'size' ] ?? 'A4';
		if ( is_string( $size ) && 'custom' === strtolower( $size ) ) {
			$custom = trim( (string) ( $pdf_opts[ 'custom_size' ] ?? '' ) );
			if ( $custom && preg_match( '/^(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*$/i', $custom, $mm ) ) {
				$w = (float) $mm[ 1 ];
				$h = (float) $mm[ 2 ];
				if ( $w > 0 && $h > 0 ) {
					return array( $w, $h );
				}
			}
			return 'A4';
		}
		return $size ?: 'A4';
	}

	/** Build mPDF instance with core settings; returns WP_Error on failure. */
	protected static function build_mpdf_instance( $pdf_opts, $gen_opts, $title, $post = null ) {
		if ( ! class_exists( '\\Mpdf\\Mpdf' ) ) {
			return new WP_Error( 'mpdf_missing', 'mPDF not available' );
		}
		$m    = $pdf_opts[ 'margins' ] ?? array();
		$mpdf = new \Mpdf\Mpdf( array(
			'format'        => self::parse_pdf_format_arg( $pdf_opts ),
			'margin_left'   => (int) ( $m[ 'l' ] ?? 15 ),
			'margin_right'  => (int) ( $m[ 'r' ] ?? 15 ),
			'margin_top'    => (int) ( $m[ 't' ] ?? 15 ),
			'margin_bottom' => (int) ( $m[ 'b' ] ?? 15 ),
		) );
		$mpdf->SetTitle( $title );
		$mpdf->SetAuthor( get_bloginfo( 'name' ) );
		return $mpdf;
	}

	/** Assemble PDF CSS with legacy and custom plus filter. */
	protected static function assemble_pdf_css( $pdf_opts, $gen_opts, $post = null ) {
		$legacy = $gen_opts[ 'css' ] ?? '';
		$css    = 'img{max-width:100%;height:auto;} figure{margin:0;}' . ( $pdf_opts[ 'custom_css' ] ?? $legacy );
		return $css . apply_filters( 'read_offline_pdf_css', '', $post );
	}

	/** Standard header block (title, featured image, author/date) used in multi exports. */
	protected static function build_post_header_html( $post, $include_featured, $include_author, $h_tag = 'h1' ) {
		$title = get_the_title( $post );
		$html  = '<' . tag_escape( $h_tag ) . '>' . esc_html( $title ) . '</' . tag_escape( $h_tag ) . '>';
		if ( $include_featured && has_post_thumbnail( $post ) ) {
			$img = get_the_post_thumbnail( $post, 'large', array( 'style' => 'max-width:100%;height:auto;margin:0 0 1em;' ) );
			if ( $img ) {
				$html .= $img;
			}
		}
		if ( $include_author ) {
			$html .= '<p class="read-offline-meta" style="font-size:0.8em;color:#555;">' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . ' – ' . esc_html( get_the_date( '', $post ) ) . '</p>';
		}
		return $html;
	}

	/* Refactored single EPUB generator using helpers */
	protected static function generate_epub( WP_Post $post, $title, $html, $path ) {
		$epub_opts = get_option( 'read_offline_settings_epub', array() );
		$gen_opts  = get_option( 'read_offline_settings_general', array() );
		$meta      = self::epub_get_meta( $epub_opts );
		$css       = self::epub_css_profile( $epub_opts, $post );
		$bodyHtml  = self::epub_build_toc_and_body( $html, $epub_opts, $post, $meta[ 'lang' ] );
		$dir       = dirname( $path );
		$basename  = preg_replace( '/\.epub$/', '', basename( $path ) );
		if ( class_exists( '\\PHPePub\\Core\\EPub' ) ) {
			try {
				$book = new \PHPePub\Core\EPub();
				$book->setTitle( $title );
				$book->setIdentifier( get_permalink( $post ), \PHPePub\Core\EPub::IDENTIFIER_URI );
				$book->setLanguage( $meta[ 'lang' ] );
				$book->setAuthor( $meta[ 'author' ], $meta[ 'author' ] );
				$book->setPublisher( $meta[ 'publisher' ], get_site_url() );
				$book->setSourceURL( get_permalink( $post ) );
				$cover = apply_filters( 'read_offline_epub_cover', null, $post, $epub_opts );
				if ( ! $cover ) {
					$cover = self::resolve_cover_image( $post, $epub_opts[ 'cover' ] ?? 'featured', $epub_opts );
				}
				if ( $cover && method_exists( $book, 'setCoverImage' ) ) {
					list( $filename, $data, $mime ) = $cover;
					$book->setCoverImage( $filename, $data, $mime );
				}
				$xhtml = self::wrap_epub_xhtml_document( $title, $meta[ 'lang' ], $bodyHtml, $css );
				$book->addChapter( sanitize_title( $title ), 'chapter1.xhtml', $xhtml );
				$book->finalize();
				$book->saveBook( $basename, $dir );
				return $path;
			} catch (\Throwable $e) {
				return new WP_Error( 'epub_failed', $e->getMessage() );
			}
		}
		// Fallback: write raw HTML (indicates missing library)
		self::write_file( $path, $html );
		return new WP_Error( 'phpepub_missing', 'PHPePub not available. Install dependencies.' );
	}

	/* Refactored combined EPUB */
	protected static function generate_combined_epub( $post_ids, $path ) {
		if ( ! class_exists( '\\PHPePub\\Core\\EPub' ) ) {
			return new WP_Error( 'phpepub_missing', 'PHPePub not available. Install dependencies.' );
		}
		$epub_opts        = get_option( 'read_offline_settings_epub', array() );
		$gen_opts         = get_option( 'read_offline_settings_general', array() );
		$meta             = self::epub_get_meta( $epub_opts );
		$css              = self::epub_css_profile( $epub_opts, null );
		$include_author   = ! empty( $gen_opts[ 'include_author' ] );
		$include_featured = ! empty( $gen_opts[ 'include_featured' ] );
		try {
			$book = new \PHPePub\Core\EPub();
			$book->setTitle( get_bloginfo( 'name' ) . ' – Combined Export' );
			$book->setIdentifier( home_url(), \PHPePub\Core\EPub::IDENTIFIER_URI );
			$book->setLanguage( $meta[ 'lang' ] );
			$book->setAuthor( $meta[ 'author' ], $meta[ 'author' ] );
			$book->setPublisher( $meta[ 'publisher' ], get_site_url() );
			$book->setSourceURL( home_url() );
			$cover = apply_filters( 'read_offline_epub_cover', null, null, $epub_opts );
			if ( ! $cover && ! empty( $post_ids ) ) {
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
				// Build header via existing helper for consistency (H1 always)
				$header = self::build_post_header_html( $post, $include_featured, $include_author );
				// TOC handling per chapter not needed; optional global inline TOC can be added here later
				$body  = $header . $content;
				$xhtml = self::wrap_epub_xhtml_document( $title, $meta[ 'lang' ], $body, $css );
				$book->addChapter( sanitize_title( $title ), 'chapter' . $index . '.xhtml', $xhtml );
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
	 * Recursively delete the plugin's auxiliary cache/db directory.
	 * Filter: read_offline_db_path to override path.
	 * Action: read_offline_db_deleted after attempt.
	 */
	public static function delete_aux_db_dir() {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			return false;
		}
		$default_path = WP_CONTENT_DIR . '/wp-loupe-db';
		$path         = apply_filters( 'read_offline_db_path', $default_path );
		$path         = wp_normalize_path( $path );
		$wc_dir       = wp_normalize_path( WP_CONTENT_DIR );
		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			// Load required classes if not already loaded.
			@include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			@include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
			do_action( 'read_offline_db_deleted', $path, false );
			return false;
		}
		$fs = new \WP_Filesystem_Direct( false );
		$ok = false;
		if (
			$fs->exists( $path ) &&
			$fs->is_dir( $path ) &&
			strpos( $path, $wc_dir ) === 0 &&
			$path !== $wc_dir
		) {
			$ok = $fs->rmdir( $path, true );
		}
		do_action( 'read_offline_db_deleted', $path, $ok );
		return $ok;
	}
}
