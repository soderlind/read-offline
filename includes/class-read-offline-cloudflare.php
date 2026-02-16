<?php
/**
 * Cloudflare Browser Rendering integration for Read Offline.
 *
 * Provides PDF generation via Cloudflare's headless browser API as an alternative to mPDF.
 * When Cloudflare credentials are configured, this engine is used automatically for better
 * rendering of modern CSS, JavaScript-heavy content, and pixel-perfect browser output.
 *
 * @link https://developers.cloudflare.com/browser-rendering/
 * @since 2.2.8
 * @package Read_Offline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cloudflare Browser Rendering PDF engine.
 *
 * Handles PDF generation via Cloudflare's headless Chromium API. Automatically used when
 * Cloudflare Account ID and API Token are configured in plugin settings. Falls back to
 * mPDF if Cloudflare generation fails or credentials are missing.
 *
 * @since 2.2.8
 */
class Read_Offline_Cloudflare {
	/**
	 * Cloudflare API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.cloudflare.com/client/v4';

	/**
	 * Check if Cloudflare credentials are configured.
	 *
	 * @return bool True if both Account ID and API Token are set.
	 */
	public static function is_configured() {
		$opts = get_option( 'read_offline_settings_general', array() );
		return ! empty( $opts[ 'cloudflare_account_id' ] ) && ! empty( $opts[ 'cloudflare_api_token' ] );
	}

	/**
	 * Get Cloudflare credentials from settings.
	 *
	 * @return array{account_id:string,api_token:string}|null Returns credentials or null if not configured.
	 */
	protected static function get_credentials() {
		$opts = get_option( 'read_offline_settings_general', array() );

		if ( empty( $opts[ 'cloudflare_account_id' ] ) || empty( $opts[ 'cloudflare_api_token' ] ) ) {
			return null;
		}

		return array(
			'account_id' => sanitize_text_field( $opts[ 'cloudflare_account_id' ] ),
			'api_token'  => sanitize_text_field( $opts[ 'cloudflare_api_token' ] ),
		);
	}

	/**
	 * Test Cloudflare API connection.
	 *
	 * Makes a simple API call to verify credentials and account access.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function test_connection() {
		$creds = self::get_credentials();

		if ( ! $creds ) {
			return new WP_Error(
				'cloudflare_not_configured',
				__( 'Cloudflare credentials not configured.', 'read-offline' )
			);
		}

		$url = self::API_BASE . '/accounts/' . $creds[ 'account_id' ];

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $creds[ 'api_token' ],
					'Content-Type'  => 'application/json',
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 200 !== $code ) {
			$message = isset( $data[ 'errors' ][ 0 ][ 'message' ] )
				? $data[ 'errors' ][ 0 ][ 'message' ]
				/* translators: %d: HTTP status code */
				: sprintf( __( 'API request failed with status %d', 'read-offline' ), $code );

			return new WP_Error(
				'cloudflare_api_error',
				$message,
				array( 'status' => $code )
			);
		}

		if ( empty( $data[ 'success' ] ) ) {
			return new WP_Error(
				'cloudflare_api_failed',
				__( 'Cloudflare API returned unsuccessful response.', 'read-offline' )
			);
		}

		return true;
	}

	/**
	 * Generate PDF using Cloudflare Browser Rendering.
	 *
	 * @param string       $html     Full HTML document to render.
	 * @param string       $path     Absolute path where PDF should be saved.
	 * @param array        $options  PDF generation options (format, margins, etc.).
	 * @param WP_Post|null $post Post object for context (optional).
	 * @return string|WP_Error Path to generated PDF or WP_Error on failure.
	 */
	public static function generate_pdf( $html, $path, $options = array(), $post = null ) {
		$creds = self::get_credentials();

		if ( ! $creds ) {
			return new WP_Error(
				'cloudflare_not_configured',
				__( 'Cloudflare credentials not configured.', 'read-offline' )
			);
		}

		// Build Puppeteer script for PDF generation.
		$pdf_options = self::build_pdf_options( $options );
		$script      = self::build_puppeteer_script( $html, $pdf_options );

		// Call Cloudflare API.
		$start_time = microtime( true );
		$url        = self::API_BASE . '/accounts/' . $creds[ 'account_id' ] . '/browser-rendering/pdf';

		/**
		 * Filter the Cloudflare API request timeout.
		 * @since 2.2.8
		 *
		 * @param int $timeout Timeout in seconds. Default 60.
		 */
		$timeout = apply_filters( 'read_offline_cloudflare_timeout', 60 );

		// Build request body for REST API
		$request_body = array(
			'html'       => $html,
			'pdfOptions' => $pdf_options,
		);


		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $creds[ 'api_token' ],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
				'timeout' => $timeout,
			)
		);

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			$data    = json_decode( $body, true );
			$message = isset( $data[ 'errors' ][ 0 ][ 'message' ] )
				? $data[ 'errors' ][ 0 ][ 'message' ]
				/* translators: %d: HTTP status code */
				: sprintf( __( 'Cloudflare API returned status %d', 'read-offline' ), $code );

			$error = new WP_Error(
				'cloudflare_api_error',
				$message,
				array(
					'status'   => $code,
					'response' => $data,
				)
			);

			self::log_error( 'API error', $message, $post, array( 'code' => $code ) );
			do_action( 'read_offline_cloudflare_pdf_failed', $post, $error, $duration );

			return $error;
		}

		// Calculate duration (ms).
		$duration = round( ( microtime( true ) - $start_time ) * 1000 );

		// The response body is the raw PDF content.
		// Save it to the specified path.
		$saved = file_put_contents( $path, $body );

		if ( false === $saved ) {
			$error = new WP_Error(
				'cloudflare_save_failed',
				__( 'Failed to save PDF file.', 'read-offline' ),
				array( 'path' => $path )
			);

			self::log_error( 'Failed to save PDF', $path, $post );
			do_action( 'read_offline_cloudflare_pdf_failed', $post, $error, $duration );

			return $error;
		}

		// Success!
		self::log_success( $post, $duration, filesize( $path ) );
		do_action( 'read_offline_cloudflare_pdf_generated', $post, $path, $duration );

		return $path;
	}

	/**
	 * Build PDF options from plugin settings for Cloudflare API.
	 *
	 * Maps mPDF-style options to Puppeteer PDF options.
	 *
	 * @param array $options Plugin PDF settings.
	 * @return array Puppeteer-compatible PDF options.
	 */
	protected static function build_pdf_options( $options ) {
		$defaults = array(
			'format'            => 'A4',
			'margins'           => array(
				't' => 15,
				'r' => 15,
				'b' => 15,
				'l' => 15,
			),
			'header'            => '',
			'footer'            => '',
			'page_numbers'      => true,
			'landscape'         => false,
			'printBackground'   => true,
			'preferCSSPageSize' => false,
			'scale'             => 1.0,
		);

		$opts = wp_parse_args( $options, $defaults );

		// Map format to Cloudflare REST API format (lowercase).
		$format_map = array(
			'A4'        => 'a4',
			'LETTER'    => 'letter',
			'LEGAL'     => 'legal',
			'A5'        => 'a5',
			'A6'        => 'a6',
			'TABLOID'   => 'tabloid',
			'B5'        => null, // Not supported, will use custom dimensions.
			'EXECUTIVE' => null,
			'CUSTOM'    => null,
		);

		$format = isset( $format_map[ $opts[ 'format' ] ] ) ? $format_map[ $opts[ 'format' ] ] : 'a4';

		// Cloudflare REST API expects margins as strings with units (e.g., "15mm" or "70px").
		$margins = array(
			'top'    => ( $opts[ 'margins' ][ 't' ] ?? 15 ) . 'mm',
			'right'  => ( $opts[ 'margins' ][ 'r' ] ?? 15 ) . 'mm',
			'bottom' => ( $opts[ 'margins' ][ 'b' ] ?? 15 ) . 'mm',
			'left'   => ( $opts[ 'margins' ][ 'l' ] ?? 15 ) . 'mm',
		);

		$pdf_opts = array(
			'format'          => $format,
			'margin'          => $margins,
			'printBackground' => (bool) $opts[ 'printBackground' ],
			'landscape'       => (bool) $opts[ 'landscape' ],
			'scale'           => (float) $opts[ 'scale' ],
		);

		// Handle header/footer templates.
		if ( ! empty( $opts[ 'header' ] ) || ! empty( $opts[ 'footer' ] ) || $opts[ 'page_numbers' ] ) {
			$pdf_opts[ 'displayHeaderFooter' ] = true;

			$header_html = ! empty( $opts[ 'header' ] ) ? $opts[ 'header' ] : '';
			$footer_html = ! empty( $opts[ 'footer' ] ) ? $opts[ 'footer' ] : '';

			// Add page numbers if enabled.
			if ( $opts[ 'page_numbers' ] && empty( $footer_html ) ) {
				$footer_html = '<div style="font-size:10px;text-align:center;width:100%;"><span class="pageNumber"></span> / <span class="totalPages"></span></div>';
			}

			if ( $header_html ) {
				$pdf_opts[ 'headerTemplate' ] = '<div style="font-size:10px;width:100%;padding:5px;">' . $header_html . '</div>';
			}

			if ( $footer_html ) {
				$pdf_opts[ 'footerTemplate' ] = '<div style="font-size:10px;width:100%;padding:5px;">' . $footer_html . '</div>';
			}
		}

		/**
		 * Filter Cloudflare PDF options before generation.
		 *
		 * @since 2.2.8
		 *
		 * @param array $pdf_opts Puppeteer PDF options.
		 * @param array $options  Original plugin settings.
		 */
		return apply_filters( 'read_offline_cloudflare_pdf_options', $pdf_opts, $options );
	}

	/**
	 * Build Puppeteer JavaScript for PDF generation.
	 *
	 * @param string $html         HTML content to render.
	 * @param array  $pdf_options  Puppeteer PDF options.
	 * @return string JavaScript code for Cloudflare Browser Rendering.
	 */
	protected static function build_puppeteer_script( $html, $pdf_options ) {
		// Escape HTML for JavaScript string embedding.
		$html_escaped = addslashes( $html );
		$options_json = wp_json_encode( $pdf_options );

		$script = <<<JAVASCRIPT
(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  // Set content.
  await page.setContent(`{$html_escaped}`, {
    waitUntil: 'networkidle0',
    timeout: 30000
  });
  
  // Generate PDF.
  const pdfOptions = {$options_json};
  const pdf = await page.pdf(pdfOptions);
  
  await browser.close();
  
  return pdf;
})();
JAVASCRIPT;

		/**
		 * Filter the Puppeteer script before sending to Cloudflare.
		 *
		 * @since 2.2.8
		 *
		 * @param string $script      JavaScript code.
		 * @param string $html        Original HTML content.
		 * @param array  $pdf_options PDF generation options.
		 */
		return apply_filters( 'read_offline_cloudflare_puppeteer_script', $script, $html, $pdf_options );
	}

	/**
	 * Log successful PDF generation.
	 *
	 * @param WP_Post|null $post     Post object.
	 * @param int          $duration Duration in milliseconds.
	 * @param int          $size     File size in bytes.
	 * @return void
	 */
	protected static function log_success( $post, $duration, $size ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$post_id = $post ? $post->ID : 0;
		$message = sprintf(
			'[Read Offline] Cloudflare PDF generated successfully: Post #%d, Duration: %dms, Size: %s',
			$post_id,
			$duration,
			size_format( $size )
		);

		error_log( $message );
	}

	/**
	 * Log error during PDF generation.
	 *
	 * @param string       $context Error context.
	 * @param string       $message Error message.
	 * @param WP_Post|null $post    Post object.
	 * @param array        $extra   Additional context data.
	 * @return void
	 */
	protected static function log_error( $context, $message, $post = null, $extra = array() ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$post_id  = $post ? $post->ID : 0;
		$log_line = sprintf(
			'[Read Offline] Cloudflare PDF error [%s]: %s (Post #%d)',
			$context,
			$message,
			$post_id
		);

		if ( ! empty( $extra ) ) {
			$log_line .= ' | ' . wp_json_encode( $extra );
		}

		error_log( $log_line );

		// Store last error in transient for admin notice.
		set_transient(
			'read_offline_cloudflare_last_error',
			array(
				'message' => $message,
				'context' => $context,
				'post_id' => $post_id,
				'time'    => time(),
			),
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Get last Cloudflare error for admin display.
	 *
	 * @return array|null Error data or null if no recent errors.
	 */
	public static function get_last_error() {
		return get_transient( 'read_offline_cloudflare_last_error' );
	}

	/**
	 * Clear last error transient.
	 *
	 * @return void
	 */
	public static function clear_last_error() {
		delete_transient( 'read_offline_cloudflare_last_error' );
	}
}
