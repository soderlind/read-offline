<?php
/**
 * Frontend UI for Read Offline.
 *
 * Provides the shortcode and auto-inserted "Save as" controls and a small JS helper
 * to call the REST endpoint and download generated files.
 *
 * @package Read_Offline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Read_Offline_Frontend {
	/**
	 * Bootstrap hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'append_save_as' ) );
		add_shortcode( 'read_offline', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Get enabled formats from settings and filter.
	 *
	 * @return array{0?:string,1?:string} Array of allowed formats (subset of [pdf, epub]).
	 */
	protected static function enabled_formats() {
		$settings = get_option( 'read_offline_settings_general', array() );
		$formats  = isset( $settings[ 'formats' ] ) ? (array) $settings[ 'formats' ] : array( 'pdf', 'epub' );
		$formats  = apply_filters( 'read_offline_enabled_formats', $formats );
		return array_values( array_intersect( array( 'pdf', 'epub' ), $formats ) );
	}

	/**
	 * Enqueue a tiny inline script for triggering the export.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$handle = 'read-offline-frontend';
		wp_register_script( $handle, '', array(), '0.1.0', true );
		$export_url = esc_url_raw( rest_url( 'read-offline/v1/export' ) );
		wp_add_inline_script( $handle, 'window.readOfflineExportUrl = ' . wp_json_encode( $export_url ) . ';', 'before' );
		$inline = <<<'JS'
		(function(){
			function getApiRoot(){
				if (window.readOfflineExportUrl) return window.readOfflineExportUrl.replace(/\/?read-offline\/v1\/export$/, '');
				if (window.wpApiSettings && window.wpApiSettings.root) return window.wpApiSettings.root;
				var link = document.querySelector('link[rel="https://api.w.org/"]');
				if (link && link.href) return link.href;
				// Fallback: core default
				return '/wp-json/';
			}
			document.addEventListener('click', function(e){
				var btn = e.target.closest('[data-read-offline-btn]');
				if(!btn) return;
				e.preventDefault();
				var postId = btn.getAttribute('data-post');
				var formatSel = btn.parentElement.querySelector('select[data-read-offline-format]');
				var format = formatSel ? formatSel.value : 'pdf';
				var nonce  = btn.getAttribute('data-nonce');
				var root = getApiRoot().replace(/\/$/, '');
				var url = (window.readOfflineExportUrl) ? window.readOfflineExportUrl : (root + '/read-offline/v1/export');
				var q = '?postId=' + encodeURIComponent(postId) + '&format=' + encodeURIComponent(format) + '&nonce=' + encodeURIComponent(nonce);
				fetch(url + q, { credentials: 'same-origin' })
					.then(function(r){ return r.json().catch(function(){ return { error: 'bad_response' }; }); })
					.then(function(res){
						if(res && res.url){ window.location.href = res.url; return; }
						var msg = 'Export failed';
						if (res && res.error) msg += ' (' + res.error + ')';
						alert(msg);
					})
					.catch(function(){ alert('Export failed'); });
			});
		})();
		JS;
		wp_add_inline_script( $handle, $inline );
		wp_enqueue_script( $handle );
	}

	/**
	 * Auto-append the UI block to post content if enabled.
	 *
	 * @param string $content Content HTML.
	 * @return string
	 */
	public static function append_save_as( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$settings = get_option( 'read_offline_settings_general', array() );
		if ( empty( $settings[ 'auto_insert' ] ) ) {
			return $content;
		}
		$post_id = get_the_ID();
		$ui      = self::render_ui( $post_id );
		return $content . $ui;
	}

	/**
	 * Shortcode handler [read_offline].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts    = shortcode_atts(
			array(
				'formats'     => implode( ',', self::enabled_formats() ),
				'include_toc' => '',
				'class'       => '',
				'post_id'     => 0,
			),
			$atts,
			'read_offline'
		);
		$post_id = intval( $atts[ 'post_id' ] ) ?: get_the_ID();
		return self::render_ui( $post_id, $atts );
	}

	/**
	 * Render the UI HTML.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $atts    Optional overrides.
	 * @return string
	 */
	protected static function render_ui( $post_id, $atts = array() ) {
		$formats = isset( $atts[ 'formats' ] ) ? array_map( 'trim', explode( ',', strtolower( $atts[ 'formats' ] ) ) ) : self::enabled_formats();
		$formats = array_values( array_intersect( array( 'pdf', 'epub' ), $formats ) );
		if ( empty( $formats ) ) {
			return '';
		}
		$nonce = wp_create_nonce( 'read_offline_export_' . $post_id );
		$class = isset( $atts[ 'class' ] ) ? sanitize_html_class( $atts[ 'class' ] ) : '';

		$opts = '';
		foreach ( $formats as $fmt ) {
			$label = strtoupper( $fmt );
			$opts .= '<option value="' . esc_attr( $fmt ) . '">' . esc_html( $label ) . '</option>';
		}
		$html = '<div class="read-offline ' . esc_attr( $class ) . '">';
		$html .= '<label>' . esc_html__( 'Save as', 'read-offline' ) . ' ';
		$html .= '<select data-read-offline-format>' . $opts . '</select></label> ';
		$html .= '<a href="#" class="button" data-read-offline-btn data-post="' . intval( $post_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Download', 'read-offline' ) . '</a>';
		$html .= '</div>';
		return $html;
	}
}
