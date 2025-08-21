<?php
/**
 * Admin UI for Read Offline.
 *
 * Settings pages, bulk actions, notices, and admin-side utilities.
 *
 * @package Read_Offline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Read_Offline_Admin {
	/**
	 * Register core admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		// Reset settings handler
		add_action( 'admin_post_read_offline_reset_settings', array( __CLASS__, 'reset_settings_action' ) );

		// Bulk actions for Posts and Pages
		add_filter( 'bulk_actions-edit-post', array( __CLASS__, 'register_bulk_actions_posts' ) );
		add_filter( 'handle_bulk_actions-edit-post', array( __CLASS__, 'handle_bulk_actions_posts' ), 10, 3 );
		add_filter( 'bulk_actions-edit-page', array( __CLASS__, 'register_bulk_actions_pages' ) );
		add_filter( 'handle_bulk_actions-edit-page', array( __CLASS__, 'handle_bulk_actions_pages' ), 10, 3 );

		// Download endpoint for generated ZIPs
		add_action( 'admin_post_read_offline_download_zip', array( __CLASS__, 'download_zip' ) );
		// Download endpoint for combined single file
		add_action( 'admin_post_read_offline_download_file', array( __CLASS__, 'download_combined_file' ) );
		// Admin notices for results
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'admin_post_read_offline_clear_cache', array( __CLASS__, 'clear_cache_action' ) );
		// Test export endpoint
		add_action( 'admin_post_read_offline_test_export', array( __CLASS__, 'test_export_action' ) );
	}

	/**
	 * Add the plugin settings page.
	 *
	 * @return void
	 */
	public static function add_settings_page() {
		$hook = add_options_page(
			__( 'Read Offline Settings', 'read-offline' ),
			__( 'Read Offline', 'read-offline' ),
			'manage_options',
			'read-offline-settings',
			array( __CLASS__, 'render_settings_page' )
		);
		if ( $hook ) {
			add_action( 'load-' . $hook, array( __CLASS__, 'add_help_tabs' ) );
		}
	}

	/**
	 * Add contextual help tabs to the settings screen.
	 *
	 * @return void
	 */
	public static function add_help_tabs() {
		$screen = get_current_screen();
		if ( ! $screen || empty( $screen->id ) ) {
			return;
		}
		// Overview
		$screen->add_help_tab(
			array(
				'id'      => 'read_offline_help_overview',
				'title'   => __( 'Overview', 'read-offline' ),
				'content' => '<p>' . esc_html__( 'Export posts and pages to PDF or EPUB. Use auto-insert to show a Save As button after content, the shortcode to place it manually, or the REST API for programmatic exports.', 'read-offline' ) . '</p>'
					. '<p><strong>' . esc_html__( 'Shortcode', 'read-offline' ) . ':</strong> <code>[read_offline]</code></p>'
					. '<p><strong>' . esc_html__( 'REST API', 'read-offline' ) . ':</strong> <code>' . esc_html( rest_url( 'read-offline/v1/export?postId={id}&format=pdf|epub' ) ) . '</code></p>',
			)
		);
		// PDF
		$screen->add_help_tab(
			array(
				'id'      => 'read_offline_help_pdf',
				'title'   => __( 'PDF settings', 'read-offline' ),
				'content' =>
					'<p>' . esc_html__( 'Choose a page size or pick Custom and provide dimensions in millimeters (e.g., 210x297). Configure margins, optional header/footer, page numbers, table of contents depth, watermark, and print protection.', 'read-offline' ) . '</p>',
			)
		);
		// EPUB
		$screen->add_help_tab(
			array(
				'id'      => 'read_offline_help_epub',
				'title'   => __( 'EPUB settings', 'read-offline' ),
				'content' =>
					'<p>' . esc_html__( 'Set metadata (author, publisher, language), include a table of contents, and pick a cover source (featured image, site logo, or custom upload via the media library). Select a CSS profile or supply custom CSS.', 'read-offline' ) . '</p>',
			)
		);
		// Troubleshooting
		$screen->add_help_tab(
			array(
				'id'      => 'read_offline_help_troubleshooting',
				'title'   => __( 'Troubleshooting', 'read-offline' ),
				'content' =>
					'<ul>'
					. '<li>' . esc_html__( 'If PDF/EPUB buttons are missing, ensure the auto-insert option is enabled or use the shortcode.', 'read-offline' ) . '</li>'
										. '<li>' . esc_html__( 'If generation fails, verify required PHP extensions (ZipArchive) and that Composer libraries (mPDF for PDF, PHPePub for EPUB) are installed.', 'read-offline' ) . '</li>'
					. '<li>' . esc_html__( 'Use the Test export tool on this page to quickly verify your setup for a specific post ID.', 'read-offline' ) . '</li>'
					. '<li>' . esc_html__( 'If old files are served, clear the plugin cache using the Clear cache button.', 'read-offline' ) . '</li>'
					. '</ul>',
			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'Need help?', 'read-offline' ) . '</strong></p>' .
			'<p>' . esc_html__( 'Use the tabs here for quick guidance. Hover field labels for additional context where available.', 'read-offline' ) . '</p>'
		);
	}

	/**
	 * Register plugin settings and defaults.
	 *
	 * @return void
	 */
	public static function register_settings() {
		// General
		register_setting(
			'read_offline_settings_general',
			'read_offline_settings_general',
			array(
				'default'           => array(
					'auto_insert'      => true,
					'formats'          => array( 'pdf', 'epub' ),
					'filename'         => '{site}-{post_slug}-{format}',
					'include_featured' => true,
					'include_author'   => true,
					'combine_bulk'     => true,
					'css'              => '',
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_general_settings' ),
			)
		);
		// PDF
		register_setting(
			'read_offline_settings_pdf',
			'read_offline_settings_pdf',
			array(
				'default'           => array(
					'size'         => 'A4',
					'custom_size'  => '',
					'margins'      => array(
						't' => 15,
						'r' => 15,
						'b' => 15,
						'l' => 15,
					),
					'header'       => '',
					'footer'       => '',
					'page_numbers' => true,
					'toc'          => true,
					'toc_depth'    => 3,
					'watermark'    => '',
					'printable'    => true,
					'fonts'        => array(),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_pdf_settings' ),
			)
		);
		// EPUB
		register_setting(
			'read_offline_settings_epub',
			'read_offline_settings_epub',
			array(
				'default'           => array(
					'meta'                       => array(
						'author'    => '',
						'publisher' => '',
						'lang'      => '',
					),
					'toc'                        => true,
					'toc_depth'                  => 3,
					'cover'                      => 'featured',
					'custom_cover_attachment_id' => 0,
					'custom_cover_url'           => '',
					'css_profile'                => 'light',
					'custom_css'                 => '',
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_epub_settings' ),
			)
		);
	}

	/**
	 * Sanitize General settings input.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_general_settings( $input ) {
		$clean                       = array();
		$clean[ 'auto_insert' ]      = ! empty( $input[ 'auto_insert' ] ) ? 1 : 0;
		$allowed_formats             = array( 'pdf', 'epub' );
		$in_formats                  = isset( $input[ 'formats' ] ) ? (array) $input[ 'formats' ] : array();
		$clean[ 'formats' ]          = array_values( array_intersect( $allowed_formats, array_map( 'sanitize_key', $in_formats ) ) );
		$clean[ 'filename' ]         = isset( $input[ 'filename' ] ) ? sanitize_text_field( wp_unslash( $input[ 'filename' ] ) ) : '{site}-{post_slug}-{format}';
		$clean[ 'include_featured' ] = ! empty( $input[ 'include_featured' ] ) ? 1 : 0;
		$clean[ 'include_author' ]   = ! empty( $input[ 'include_author' ] ) ? 1 : 0;
		$clean[ 'combine_bulk' ]     = ! empty( $input[ 'combine_bulk' ] ) ? 1 : 0;
		$clean[ 'css' ]              = isset( $input[ 'css' ] ) ? sanitize_textarea_field( wp_unslash( $input[ 'css' ] ) ) : '';
		return $clean;
	}

	/**
	 * Sanitize PDF settings input.
	 *
	 * Validates custom size (e.g., 210x297 mm), clamps TOC depth, normalizes margins to ints.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_pdf_settings( $input ) {
		$clean           = array();
		$allowed_sizes   = array( 'A4', 'Letter', 'Legal', 'A5', 'A6', 'B5', 'Tabloid', 'Executive', 'Custom' );
		$size            = isset( $input[ 'size' ] ) ? (string) $input[ 'size' ] : 'A4';
		$size_upper      = strtoupper( $size );
		$clean[ 'size' ] = in_array( $size_upper, $allowed_sizes, true ) ? $size_upper : 'A4';
		$custom_size     = isset( $input[ 'custom_size' ] ) ? trim( (string) $input[ 'custom_size' ] ) : '';
		if ( preg_match( '/^\s*(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)\s*$/i', $custom_size, $m ) ) {
			$w                      = (float) $m[ 1 ];
			$h                      = (float) $m[ 2 ];
			$clean[ 'custom_size' ] = ( $w > 0 && $h > 0 ) ? ( $w . 'x' . $h ) : '';
		} else {
			$clean[ 'custom_size' ] = '';
		}
		$clean[ 'margins' ] = array();
		$defaults_m         = array( 't' => 15, 'r' => 15, 'b' => 15, 'l' => 15 );
		foreach ( $defaults_m as $k => $def ) {
			$val                      = isset( $input[ 'margins' ][ $k ] ) ? (int) $input[ 'margins' ][ $k ] : $def;
			$val                      = max( 0, min( 200, $val ) );
			$clean[ 'margins' ][ $k ] = $val;
		}
		$clean[ 'header' ]       = isset( $input[ 'header' ] ) ? wp_kses_post( wp_unslash( $input[ 'header' ] ) ) : '';
		$clean[ 'footer' ]       = isset( $input[ 'footer' ] ) ? wp_kses_post( wp_unslash( $input[ 'footer' ] ) ) : '';
		$clean[ 'page_numbers' ] = ! empty( $input[ 'page_numbers' ] ) ? 1 : 0;
		$clean[ 'toc' ]          = ! empty( $input[ 'toc' ] ) ? 1 : 0;
		$depth                   = isset( $input[ 'toc_depth' ] ) ? (int) $input[ 'toc_depth' ] : 3;
		$clean[ 'toc_depth' ]    = max( 1, min( 6, $depth ) );
		$clean[ 'watermark' ]    = isset( $input[ 'watermark' ] ) ? sanitize_text_field( wp_unslash( $input[ 'watermark' ] ) ) : '';
		$clean[ 'printable' ]    = ! empty( $input[ 'printable' ] ) ? 1 : 0;
		$clean[ 'fonts' ]        = array();
		if ( ! empty( $input[ 'fonts' ] ) && is_array( $input[ 'fonts' ] ) ) {
			foreach ( $input[ 'fonts' ] as $font ) {
				$clean[ 'fonts' ][] = sanitize_text_field( $font );
			}
		}
		return $clean;
	}

	/**
	 * Sanitize EPUB settings input.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_epub_settings( $input ) {
		$clean                                 = array();
		$clean[ 'meta' ]                       = array();
		$clean[ 'meta' ][ 'author' ]           = isset( $input[ 'meta' ][ 'author' ] ) ? sanitize_text_field( wp_unslash( $input[ 'meta' ][ 'author' ] ) ) : '';
		$clean[ 'meta' ][ 'publisher' ]        = isset( $input[ 'meta' ][ 'publisher' ] ) ? sanitize_text_field( wp_unslash( $input[ 'meta' ][ 'publisher' ] ) ) : '';
		$clean[ 'meta' ][ 'lang' ]             = isset( $input[ 'meta' ][ 'lang' ] ) ? sanitize_text_field( wp_unslash( $input[ 'meta' ][ 'lang' ] ) ) : '';
		$clean[ 'toc' ]                        = ! empty( $input[ 'toc' ] ) ? 1 : 0;
		$depth                                 = isset( $input[ 'toc_depth' ] ) ? (int) $input[ 'toc_depth' ] : 3;
		$clean[ 'toc_depth' ]                  = max( 1, min( 6, $depth ) );
		$allowed_covers                        = array( 'featured', 'logo', 'custom' );
		$cover                                 = isset( $input[ 'cover' ] ) ? sanitize_key( $input[ 'cover' ] ) : 'featured';
		$clean[ 'cover' ]                      = in_array( $cover, $allowed_covers, true ) ? $cover : 'featured';
		$clean[ 'custom_cover_attachment_id' ] = isset( $input[ 'custom_cover_attachment_id' ] ) ? max( 0, (int) $input[ 'custom_cover_attachment_id' ] ) : 0;
		$clean[ 'custom_cover_url' ]           = isset( $input[ 'custom_cover_url' ] ) ? esc_url_raw( $input[ 'custom_cover_url' ] ) : '';
		$allowed_profiles                      = array( 'light', 'dark', 'none', 'custom' );
		$profile                               = isset( $input[ 'css_profile' ] ) ? sanitize_key( $input[ 'css_profile' ] ) : 'light';
		$clean[ 'css_profile' ]                = in_array( $profile, $allowed_profiles, true ) ? $profile : 'light';
		$clean[ 'custom_css' ]                 = isset( $input[ 'custom_css' ] ) ? sanitize_textarea_field( wp_unslash( $input[ 'custom_css' ] ) ) : '';
		return $clean;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		$tabs          = array(
			'general' => __( 'General', 'read-offline' ),
			'pdf'     => __( 'PDF', 'read-offline' ),
			'epub'    => __( 'EPUB', 'read-offline' ),
		);
		$requested_tab = isset( $_GET[ 'tab' ] ) ? sanitize_key( wp_unslash( $_GET[ 'tab' ] ) ) : '';
		$current_tab   = ( $requested_tab && isset( $tabs[ $requested_tab ] ) ) ? $requested_tab : 'general';
		if ( 'epub' === $current_tab && function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		// Health checks
		$health = array(
			'zip'  => class_exists( 'ZipArchive' ),
			'pdf'  => class_exists( '\\Mpdf\\Mpdf' ),
			'epub' => class_exists( '\\PHPePub\\Core\\EPub' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Read Offline Settings', 'read-offline' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab => $label ) : ?>
					<?php $tab_class = 'nav-tab' . ( $current_tab === $tab ? ' nav-tab-active' : '' ); ?>
					<a href="?page=read-offline-settings&tab=<?php echo esc_attr( $tab ); ?>"
						class="<?php echo esc_attr( $tab_class ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</h2>
			<style>
				.read-offline-card {
					background: #fff;
					padding: 16px;
					border: 1px solid #ccd0d4;
					border-radius: 4px;
					margin-top: 12px;
				}

				.read-offline-layout {
					display: flex;
					gap: 16px;
					align-items: flex-start
				}

				.read-offline-main {
					flex: 2;
					min-width: 0
				}

				.read-offline-aside {
					flex: 1;
					min-width: 260px
				}

				.read-offline-grid {
					display: grid;
					grid-template-columns: 240px 1fr;
					gap: 12px 20px;
					align-items: start;
				}

				.read-offline-grid>label {
					align-self: start;
					justify-self: end;
					text-align: right;
					margin: 0;
					font-weight: 600;
					line-height: 1.3;
				}

				.read-offline-grid .full {
					grid-column: 1/-1
				}

				.read-offline-field-desc {
					color: #555;
					font-size: 12px;
					margin-top: 4px;
				}

				.read-offline-help-tip {
					display: inline-block;
					margin-left: 6px;
					color: #646970;
					cursor: pointer;
					border: 1px solid #ccd0d4;
					border-radius: 50%;
					width: 16px;
					height: 16px;
					line-height: 14px;
					text-align: center;
					font-size: 11px;
					background: #f6f7f7
				}

				.read-offline-help-tip:hover {
					background: #eef0f1
				}

				.read-offline-help-popup {
					position: absolute;
					z-index: 100000;
					background: #fff;
					border: 1px solid #ccd0d4;
					border-radius: 4px;
					box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
					padding: 10px 28px 10px 10px;
					max-width: 320px;
					max-height: 260px;
					overflow: auto;
					font-size: 12px;
					color: #1d2327
				}

				.read-offline-help-popup::after {
					content: "";
					position: absolute;
					top: -6px;
					left: 12px;
					border-width: 6px;
					border-style: solid;
					border-color: transparent transparent #ccd0d4 transparent
				}

				.read-offline-help-popup::before {
					content: "";
					position: absolute;
					top: -5px;
					left: 12px;
					border-width: 6px;
					border-style: solid;
					border-color: transparent transparent #fff transparent
				}

				.read-offline-help-close {
					position: absolute;
					top: 6px;
					right: 6px;
					border: 1px solid #ccd0d4;
					border-radius: 50%;
					width: 20px;
					height: 20px;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					background: #f6f7f7;
					color: #1d2327;
					cursor: pointer;
					line-height: 1;
					font-size: 12px
				}

				.read-offline-help-close:hover {
					background: #eef0f1
				}

				.read-offline-cover-preview img {
					max-width: 160px;
					height: auto;
					display: block;
					margin-top: 8px;
					border: 1px solid #e0e0e0;
				}

				.read-offline-health {
					margin-top: 8px;
				}

				.read-offline-health li {
					margin: 4px 0;
				}

				.read-offline-ok {
					color: #1a7f37;
				}

				.read-offline-warn {
					color: #9a6700;
				}

				.read-offline-chips {
					margin-top: 6px
				}

				.read-offline-chip {
					display: inline-block;
					border: 1px solid #ccd0d4;
					border-radius: 12px;
					padding: 2px 8px;
					margin: 2px;
					font-size: 11px;
					background: #f6f7f7;
					cursor: pointer
				}

								/* EPUB CSS profile preview */
								.read-offline-epub-css-preview-wrapper { margin-top: 6px; }
								.read-offline-epub-css-preview { border:1px solid #ccd0d4; padding:8px 10px; font-size:12px; line-height:1.4; border-radius:4px; display:flex; gap:12px; align-items:flex-start; }
								.read-offline-epub-css-preview samp { display:block; font-family:system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Arial, sans-serif; }
								.read-offline-epub-css-preview small { display:block; opacity:.75; margin-top:4px; }
								.read-offline-epub-css-preview code { background:rgba(0,0,0,.07); padding:1px 4px; border-radius:3px; }
								.read-offline-epub-css-preview[data-profile="light"] { background:#ffffff; color:#1d2327; }
								.read-offline-epub-css-preview[data-profile="dark"] { background:#1d2327; color:#f5f5f5; }
								.read-offline-epub-css-preview[data-profile="none"] { background:linear-gradient(45deg,#fafafa 25%,#f0f0f0 25%,#f0f0f0 50%,#fafafa 50%,#fafafa 75%,#f0f0f0 75%,#f0f0f0); background-size:18px 18px; color:#1d2327; }
								.read-offline-epub-css-preview-badge { font-size:10px; letter-spacing:.5px; text-transform:uppercase; font-weight:600; padding:2px 6px; border:1px solid currentColor; border-radius:10px; align-self:flex-start; }
								.read-offline-epub-css-preview[data-profile="dark"] .read-offline-epub-css-preview-badge { color:#f5f5f5; }
								.read-offline-epub-css-preview[data-profile="light"] .read-offline-epub-css-preview-badge { color:#1d2327; }
								.read-offline-epub-css-preview[data-profile="none"] .read-offline-epub-css-preview-badge { color:#444; }

				.read-offline-actions {
					display: flex;
					gap: 8px;
					flex-wrap: wrap;
					margin-top: 8px
				}

				.read-offline-kbd {
					background: #f6f7f7;
					border: 1px solid #ccd0d4;
					border-radius: 3px;
					padding: 2px 4px
				}
			</style>

			<div class="read-offline-layout">
								<div class="read-offline-main">
					<form method="post" action="options.php">
						<?php
						if ( 'general' === $current_tab ) {
							settings_fields( 'read_offline_settings_general' );
							$options = get_option( 'read_offline_settings_general' );
							?>
							<div class="read-offline-card">
								<div class="read-offline-grid">
									<label><?php _e( 'Auto-insert Save As button', 'read-offline' ); ?>
										<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
											aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
											data-help="<?php echo esc_attr__( 'Automatically appends the Save As controls after single post content. Disable if you prefer placing the shortcode manually.', 'read-offline' ); ?>">?</span>
									</label>
									<div>
										<input type="checkbox" name="read_offline_settings_general[auto_insert]" value="1" <?php checked( ! empty( $options[ 'auto_insert' ] ) ); ?> />
										<p class="read-offline-field-desc">
											<?php esc_html_e( 'Append the Save As control after post content automatically.', 'read-offline' ); ?>
										</p>
									</div>

									<label><?php _e( 'Default formats', 'read-offline' ); ?>
										<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
											aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
											data-help="<?php echo esc_attr__( 'Choose which formats show up by default in the Save As UI. You can still filter these via hooks.', 'read-offline' ); ?>">?</span>
									</label>
									<div>
										<label><input type="checkbox" name="read_offline_settings_general[formats][]" value="pdf" <?php checked( in_array( 'pdf', (array) ( $options[ 'formats' ] ?? array() ), true ) ); ?> /> PDF</label>
										<label style="margin-left:12px;"><input type="checkbox" name="read_offline_settings_general[formats][]" value="epub" <?php checked( in_array( 'epub', (array) ( $options[ 'formats' ] ?? array() ), true ) ); ?> /> EPUB</label>
									</div>

									<label><?php _e( 'Filename template', 'read-offline' ); ?>
										<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
											aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
											data-help="<?php echo esc_attr__( 'Use placeholders like {site}, {post_slug}, {title}, {format}, {date}, {lang}. The preview below shows an example.', 'read-offline' ); ?>">?</span>
									</label>
									<div>
										<input id="ro-filename" type="text" name="read_offline_settings_general[filename]" value="<?php echo esc_attr( $options[ 'filename' ] ?? '' ); ?>" class="regular-text" />
										<div class="read-offline-chips" id="ro-chips"></div>
										<p class="read-offline-field-desc">
											<?php esc_html_e( 'Click to insert placeholders. Preview updates live.', 'read-offline' ); ?>
										</p>
										<p class="read-offline-field-desc"><strong><?php esc_html_e( 'Preview:', 'read-offline' ); ?></strong> <span id="ro-filename-preview"></span></p>
									</div>

									<label><?php _e( 'Include featured image as cover', 'read-offline' ); ?>
										<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
											aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
											data-help="<?php echo esc_attr__( 'If enabled, the post’s featured image will be used as a cover in both PDF and EPUB when appropriate.', 'read-offline' ); ?>">?</span>
									</label>
									<div><input type="checkbox" name="read_offline_settings_general[include_featured]" value="1" <?php checked( ! empty( $options[ 'include_featured' ] ) ); ?> /></div>

									<label><?php _e( 'Include author/date', 'read-offline' ); ?>
										<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
											aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
											data-help="<?php echo esc_attr__( 'Adds basic metadata (author and publish date) near the top of the exported document.', 'read-offline' ); ?>">?</span>
									</label>
									<div><input type="checkbox" name="read_offline_settings_general[include_author]" value="1" <?php checked( ! empty( $options[ 'include_author' ] ) ); ?> /></div>

									<label><?php _e( 'Combine bulk exports', 'read-offline' ); ?>
										<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
											aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
											data-help="<?php echo esc_attr__( 'When enabled, selecting multiple posts/pages in bulk export produces one combined PDF/EPUB instead of a ZIP of separate files.', 'read-offline' ); ?>">?</span>
									</label>
									<div><?php
									// Show as checked if key absent (legacy installs) or explicitly truthy.
									$combine_default_checked = ! array_key_exists( 'combine_bulk', (array) $options ) || ! empty( $options[ 'combine_bulk' ] );
									?>
										<input type="checkbox" name="read_offline_settings_general[combine_bulk]" value="1" <?php checked( $combine_default_checked ); ?> />
										<p class="read-offline-field-desc"><?php esc_html_e( 'Uncheck to revert to per-post files zipped together.', 'read-offline' ); ?></p>
									</div>

									<label><?php _e( 'Custom CSS for PDF', 'read-offline' ); ?>
										<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
											aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
											data-help="<?php echo esc_attr__( 'Additional CSS appended to the PDF output. Useful for minor layout tweaks.', 'read-offline' ); ?>">?</span>
									</label>
									<div class="full"><textarea name="read_offline_settings_general[css]" class="large-text" rows="4"><?php echo esc_textarea( $options[ 'css' ] ?? '' ); ?></textarea></div>
								</div>
							</div>
							<?php
						} elseif ( 'pdf' === $current_tab ) {
							settings_fields( 'read_offline_settings_pdf' );
							$options = get_option( 'read_offline_settings_pdf' );
							?>
								<div class="read-offline-card">
									<div class="read-offline-grid">
										<label><?php _e( 'Page size', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Select a standard size or choose Custom to enter width x height in millimeters (e.g., 210x297).', 'read-offline' ); ?>">?</span>
										</label>
										<div>
											<select id="ro-pdf-size" name="read_offline_settings_pdf[size]">
												<?php
												$sizes   = array( 'A4', 'Letter', 'Legal', 'A5', 'A6', 'B5', 'Tabloid', 'Executive', 'Custom' );
												$current = strtoupper( $options[ 'size' ] ?? 'A4' );
												foreach ( $sizes as $s ) {
													printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $s ), selected( $current, strtoupper( $s ), false ) );
												}
												?>
											</select>
											<div id="ro-pdf-size-custom" style="margin-top:8px;display:none;">
												<label
													for="ro-pdf-custom"><?php _e( 'Custom size (mm):', 'read-offline' ); ?></label>
												<input id="ro-pdf-custom" type="text" name="read_offline_settings_pdf[custom_size]"
													value="<?php echo esc_attr( $options[ 'custom_size' ] ?? '' ); ?>"
													placeholder="210x297" class="regular-text" />
												<p class="read-offline-field-desc">
													<?php esc_html_e( 'Format: width x height in millimeters, e.g., 210x297', 'read-offline' ); ?>
												</p>
											</div>
											<script>(function () { function toggleCustom() { var v = document.getElementById('ro-pdf-size').value.toLowerCase(); document.getElementById('ro-pdf-size-custom').style.display = (v === 'custom') ? 'block' : 'none'; } document.getElementById('ro-pdf-size').addEventListener('change', toggleCustom); toggleCustom(); })();</script>
										</div>

										<label><?php _e( 'Margins (t,r,b,l)', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Enter top, right, bottom, left margins in millimeters.', 'read-offline' ); ?>">?</span>
										</label>
										<div>
											<input type="text" name="read_offline_settings_pdf[margins][t]"
												value="<?php echo esc_attr( $options[ 'margins' ][ 't' ] ?? '' ); ?>" size="2" />
											<input type="text" name="read_offline_settings_pdf[margins][r]"
												value="<?php echo esc_attr( $options[ 'margins' ][ 'r' ] ?? '' ); ?>" size="2" />
											<input type="text" name="read_offline_settings_pdf[margins][b]"
												value="<?php echo esc_attr( $options[ 'margins' ][ 'b' ] ?? '' ); ?>" size="2" />
											<input type="text" name="read_offline_settings_pdf[margins][l]"
												value="<?php echo esc_attr( $options[ 'margins' ][ 'l' ] ?? '' ); ?>" size="2" />
										</div>

										<label><?php _e( 'Header', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Optional HTML header shown on each page. Keep it simple for best results.', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="text" name="read_offline_settings_pdf[header]"
												value="<?php echo esc_attr( $options[ 'header' ] ?? '' ); ?>"
												class="regular-text" /></div>

										<label><?php _e( 'Footer', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Optional HTML footer. Use {PAGENO}/{nbpg} via Page numbers setting for pagination.', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="text" name="read_offline_settings_pdf[footer]"
												value="<?php echo esc_attr( $options[ 'footer' ] ?? '' ); ?>"
												class="regular-text" /></div>

										<label><?php _e( 'Page numbers', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Adds a simple {PAGENO}/{nbpg} footer when no custom footer is set.', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="checkbox" name="read_offline_settings_pdf[page_numbers]" value="1" <?php checked( ! empty( $options[ 'page_numbers' ] ) ); ?> /></div>

										<label><?php _e( 'Table of Contents', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Generates a TOC before the content. Depth controls which heading levels are included.', 'read-offline' ); ?>">?</span>
										</label>
										<div>
											<input type="checkbox" name="read_offline_settings_pdf[toc]" value="1" <?php checked( ! empty( $options[ 'toc' ] ) ); ?> />
											<?php _e( 'Depth:', 'read-offline' ); ?>
											<input type="number" name="read_offline_settings_pdf[toc_depth]"
												value="<?php echo esc_attr( $options[ 'toc_depth' ] ?? 3 ); ?>" min="1" max="6"
												size="2" />
										</div>

										<label><?php _e( 'Watermark text', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Optional translucent text placed behind content on each page.', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="text" name="read_offline_settings_pdf[watermark]"
												value="<?php echo esc_attr( $options[ 'watermark' ] ?? '' ); ?>"
												class="regular-text" /></div>

										<label><?php _e( 'Printable', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'When unchecked, restricts printing via PDF permissions (not bulletproof).', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="checkbox" name="read_offline_settings_pdf[printable]" value="1" <?php checked( ! empty( $options[ 'printable' ] ) ); ?> /></div>
									</div>
								</div>
								<?php
						} else { // epub
							settings_fields( 'read_offline_settings_epub' );
							$options = get_option( 'read_offline_settings_epub' );
							?>
								<div class="read-offline-card">
									<div class="read-offline-grid">
										<label><?php _e( 'Author', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Overrides the EPUB author metadata. Leave blank to default to site name.', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="text" name="read_offline_settings_epub[meta][author]"
												value="<?php echo esc_attr( $options[ 'meta' ][ 'author' ] ?? '' ); ?>"
												class="regular-text" /></div>

										<label><?php _e( 'Publisher', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'EPUB publisher metadata. Leave blank to default to site name.', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="text" name="read_offline_settings_epub[meta][publisher]"
												value="<?php echo esc_attr( $options[ 'meta' ][ 'publisher' ] ?? '' ); ?>"
												class="regular-text" /></div>

										<label><?php _e( 'Language', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'BCP 47 language tag (e.g., en-US). Leave blank to auto-detect from site locale.', 'read-offline' ); ?>">?</span>
										</label>
										<div><input type="text" name="read_offline_settings_epub[meta][lang]"
												value="<?php echo esc_attr( $options[ 'meta' ][ 'lang' ] ?? '' ); ?>"
												class="regular-text" /></div>

										<label><?php _e( 'Table of Contents', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Adds an inline TOC at the top of the EPUB. Depth controls which heading levels are included.', 'read-offline' ); ?>">?</span>
										</label>
										<div>
											<input type="checkbox" name="read_offline_settings_epub[toc]" value="1" <?php checked( ! empty( $options[ 'toc' ] ) ); ?> />
											<?php _e( 'Depth:', 'read-offline' ); ?>
											<input type="number" name="read_offline_settings_epub[toc_depth]"
												value="<?php echo esc_attr( $options[ 'toc_depth' ] ?? 3 ); ?>" min="1" max="6"
												size="2" />
										</div>

										<label><?php _e( 'Cover source', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Pick the image used as the EPUB cover. Custom lets you choose from the media library.', 'read-offline' ); ?>">?</span>
										</label>
										<div>
											<select name="read_offline_settings_epub[cover]" id="read-offline-epub-cover-source">
												<option value="featured" <?php selected( $options[ 'cover' ] ?? '', 'featured' ); ?>><?php _e( 'Featured Image', 'read-offline' ); ?></option>
												<option value="logo" <?php selected( $options[ 'cover' ] ?? '', 'logo' ); ?>>
													<?php _e( 'Site Logo', 'read-offline' ); ?>
												</option>
												<option value="custom" <?php selected( $options[ 'cover' ] ?? '', 'custom' ); ?>>
													<?php _e( 'Custom Upload', 'read-offline' ); ?>
												</option>
											</select>
											<div id="read-offline-custom-cover" style="margin-top:8px; display:none;">
												<input type="hidden" id="read-offline-custom-cover-id"
													name="read_offline_settings_epub[custom_cover_attachment_id]"
													value="<?php echo esc_attr( (string) ( $options[ 'custom_cover_attachment_id' ] ?? 0 ) ); ?>" />
												<button type="button" class="button"
													id="read-offline-select-cover"><?php esc_html_e( 'Select image', 'read-offline' ); ?></button>
												<button type="button" class="button" id="read-offline-remove-cover"
													style="display:none; margin-left:6px;">&times;
													<?php esc_html_e( 'Remove', 'read-offline' ); ?></button>
												<div class="read-offline-cover-preview" id="read-offline-cover-preview"></div>
												<p class="read-offline-field-desc">
													<?php esc_html_e( 'Pick an image from the media library to use as EPUB cover.', 'read-offline' ); ?>
												</p>
											</div>
										</div>

										<label><?php _e( 'CSS Profile', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Choose a base stylesheet for EPUB. Custom allows full control via the field below.', 'read-offline' ); ?>">?</span>
										</label>
										<div>
											<select name="read_offline_settings_epub[css_profile]">
												<option value="light" <?php selected( $options[ 'css_profile' ] ?? '', 'light' ); ?>><?php _e( 'Light', 'read-offline' ); ?></option>
												<option value="dark" <?php selected( $options[ 'css_profile' ] ?? '', 'dark' ); ?>>
													<?php _e( 'Dark', 'read-offline' ); ?>
												</option>
												<option value="none" <?php selected( $options[ 'css_profile' ] ?? '', 'none' ); ?>>
													<?php _e( 'None', 'read-offline' ); ?>
												</option>
												<option value="custom" <?php selected( $options[ 'css_profile' ] ?? '', 'custom' ); ?>><?php _e( 'Custom', 'read-offline' ); ?></option>
											</select>
													<div class="read-offline-epub-css-preview-wrapper">
														<div id="read-offline-epub-css-preview" class="read-offline-epub-css-preview" data-profile="<?php echo esc_attr( $options['css_profile'] ?? 'light' ); ?>">
															<span class="read-offline-epub-css-preview-badge" id="read-offline-epub-css-preview-badge"></span>
															<div>
																<samp><strong><?php esc_html_e( 'Heading Example', 'read-offline' ); ?></strong></samp>
																<samp style="font-size:11px;">Lorem ipsum dolor sit amet, <code>&lt;strong&gt;</code> elit.</samp>
																<small id="read-offline-epub-css-preview-desc"></small>
															</div>
														</div>
													</div>
										</div>

										<label><?php _e( 'Custom CSS', 'read-offline' ); ?>
											<span class="read-offline-help-tip" role="button" tabindex="0" aria-haspopup="dialog"
												aria-label="<?php echo esc_attr__( 'Help', 'read-offline' ); ?>"
												data-help="<?php echo esc_attr__( 'Additional CSS injected into the EPUB chapter. Keep it simple; avoid external URLs.', 'read-offline' ); ?>">?</span>
										</label>
										<div class="full"><textarea name="read_offline_settings_epub[custom_css]" class="large-text"
												rows="4"><?php echo esc_textarea( $options[ 'custom_css' ] ?? '' ); ?></textarea>
										</div>
									</div>
								</div>
								<script>
									(function ($) {
										// Chips for filename template
										var chips = ['{site}', '{post_slug}', '{post_id}', '{title}', '{format}', '{date}', '{lang}'];
										var $chips = $('#ro-chips');
										chips.forEach(function (c) { $chips.append('<span class="read-offline-chip" data-chip="' + c + '">' + c + '</span>'); });
										function updatePreview() {
											var t = $('#ro-filename').val() || '';
											var sample = { '{site}': 'mysite', '{post_slug}': 'example-post', '{post_id}': '123', '{title}': 'example-post', '{format}': 'pdf', '{date}': '20250821', '{lang}': 'en_US' };
											Object.keys(sample).forEach(function (k) { t = t.split(k).join(sample[k]); });
											$('#ro-filename-preview').text(t);
										}
										$(document).on('click', '.read-offline-chip', function () {
											var chip = $(this).data('chip');
											var $inp = $('#ro-filename')[0];
											if (!$inp) return; var start = $inp.selectionStart || 0; var end = $inp.selectionEnd || 0; var v = $inp.value;
											$inp.value = v.substring(0, start) + chip + v.substring(end);
											$('#ro-filename').trigger('input').focus();
											$inp.selectionStart = $inp.selectionEnd = start + chip.length;
										});
										$(document).on('input', '#ro-filename', updatePreview);
										$(function () { updatePreview(); });
										function toggleCustomCover() {
											var v = $('#read-offline-epub-cover-source').val();
											var $wrap = $('#read-offline-custom-cover');
											if (v === 'custom') { $wrap.show(); } else { $wrap.hide(); }
										}
										function renderPreview(attachment) {
											var $prev = $('#read-offline-cover-preview');
											$prev.empty();
											if (attachment && attachment.url) {
												var url = (attachment.sizes && (attachment.sizes.medium || attachment.sizes.thumbnail)) ? (attachment.sizes.medium || attachment.sizes.thumbnail).url : attachment.url;
												$prev.append('<img src="' + url + '" alt="cover" />');
												$('#read-offline-remove-cover').show();
											} else {
												$('#read-offline-remove-cover').hide();
											}
										}
										$(document).on('change', '#read-offline-epub-cover-source', toggleCustomCover);
										$(document).on('click', '#read-offline-select-cover', function (e) {
											e.preventDefault();
											var frame = wp.media({ title: '<?php echo esc_js( __( 'Select EPUB cover', 'read-offline' ) ); ?>', multiple: false, library: { type: 'image' } });
											frame.on('select', function () {
												var att = frame.state().get('selection').first().toJSON();
												$('#read-offline-custom-cover-id').val(att.id);
												renderPreview(att);
											});
											frame.open();
										});
										$(document).on('click', '#read-offline-remove-cover', function (e) {
											e.preventDefault();
											$('#read-offline-custom-cover-id').val('0');
											$('#read-offline-cover-preview').empty();
											$(this).hide();
										});
										$(function () {
											toggleCustomCover();
											var existingId = $('#read-offline-custom-cover-id').val();
											if (existingId && parseInt(existingId, 10) > 0) {
												try { wp.media.attachment(existingId).fetch().then(function (att) { renderPreview(att.toJSON()); }); } catch (err) { }
											}
										});
									})(jQuery);
								</script>
								<script>
									(function($){
										function updateEpubCssPreview(){
											var profile = $('[name="read_offline_settings_epub[css_profile]"]').val() || 'light';
											var $box = $('#read-offline-epub-css-preview');
											if(!$box.length) return;
											$box.attr('data-profile', profile);
											var badge = profile.charAt(0).toUpperCase()+profile.slice(1);
											$('#read-offline-epub-css-preview-badge').text(badge);
											var desc='';
											if(profile==='light') desc='<?php echo esc_js( __( 'Clean white background with dark text.', 'read-offline' ) ); ?>';
											else if(profile==='dark') desc='<?php echo esc_js( __( 'Dark background with light text for low-light reading.', 'read-offline' ) ); ?>';
											else if(profile==='none') desc='<?php echo esc_js( __( 'No base styling; inherits minimal reader defaults.', 'read-offline' ) ); ?>';
											else if(profile==='custom') desc='<?php echo esc_js( __( 'Will use only your Custom CSS below.', 'read-offline' ) ); ?>';
											$('#read-offline-epub-css-preview-desc').text(desc);
										}
										$(document).on('change','[name="read_offline_settings_epub[css_profile]"]',updateEpubCssPreview);
										$(updateEpubCssPreview);
									})(jQuery);
								</script>
								<?php
						}
						submit_button();
						?>
					</form>
					<?php if ( 'general' === $current_tab ) : ?>
						<hr />
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
							style="margin-top:12px;">
							<?php wp_nonce_field( 'read_offline_clear_cache', '_roc_nonce' ); ?>
							<input type="hidden" name="action" value="read_offline_clear_cache" />
							<?php submit_button( __( 'Clear cache', 'read-offline' ), 'secondary', 'submit', false ); ?>
						</form>
						<div class="read-offline-card" style="margin-top:12px;">
							<?php $general_opts = get_option( 'read_offline_settings_general', array() ); ?>
							<h2><?php esc_html_e( 'Test export', 'read-offline' ); ?></h2>
							<p class="read-offline-field-desc">
								<?php esc_html_e( 'Run a quick export for a known post ID to verify the setup.', 'read-offline' ); ?>
							</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'read_offline_test_export', '_rot_nonce' ); ?>
								<input type="hidden" name="action" value="read_offline_test_export" />
								<input type="number" name="post_id"
									value="<?php echo isset( $general_opts[ 'last_test_post_id' ] ) ? intval( $general_opts[ 'last_test_post_id' ] ) : 0; ?>"
									min="1" style="width:120px;" />
								&nbsp;
								<select name="format">
									<option value="pdf">PDF</option>
									<option value="epub">EPUB</option>
								</select>
								&nbsp;
								<?php submit_button( __( 'Test export', 'read-offline' ), 'secondary', 'submit', false ); ?>
							</form>
						</div>
					<?php endif; ?>
				</div><!-- /.read-offline-main -->
				<div class="read-offline-aside">
					<div class="read-offline-card">
						<h2><?php esc_html_e( 'Shortcode & REST', 'read-offline' ); ?></h2>
						<p>[read_offline]</p>
						<p class="read-offline-field-desc">
							<?php echo esc_html( rest_url( 'read-offline/v1/export?postId={id}&format=pdf|epub' ) ); ?>
						</p>
					</div>
					<div class="read-offline-card">
						<h2><?php esc_html_e( 'Placeholders', 'read-offline' ); ?></h2>
						<p class="read-offline-field-desc">{site}, {post_slug}, {post_id}, {title}, {format}, {date}, {lang}</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'read_offline_reset_settings', '_ror_nonce' ); ?>
							<input type="hidden" name="action" value="read_offline_reset_settings" />
							<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>" />
							<?php submit_button( __( 'Reset this tab to defaults', 'read-offline' ), 'small', 'submit', false ); ?>
						</form>
					</div>
				</div><!-- /.read-offline-aside -->
			</div><!-- /.read-offline-layout -->
		</div>
		<script>
			(function () {
				var popup = null, owner = null, content = null, closeBtn = null;
				function ensurePopup() {
					if (!popup) {
						popup = document.createElement('div');
						popup.className = 'read-offline-help-popup';
						popup.id = 'read-offline-help-popup';
						popup.setAttribute('role', 'dialog');
						popup.setAttribute('aria-modal', 'false');
						popup.setAttribute('aria-hidden', 'true');
						popup.style.display = 'none';
						popup.tabIndex = -1;
						content = document.createElement('div');
						content.className = 'read-offline-help-content';
						closeBtn = document.createElement('button');
						closeBtn.type = 'button';
						closeBtn.className = 'read-offline-help-close';
						closeBtn.setAttribute('aria-label', 'Close help');
						closeBtn.setAttribute('title', '');
						closeBtn.innerHTML = '\u00D7';
						closeBtn.addEventListener('click', hide);
						popup.appendChild(content);
						popup.appendChild(closeBtn);
						document.body.appendChild(popup);
					}
					return popup;
				}
				function hide() {
					if (popup) {
						popup.style.display = 'none';
						popup.setAttribute('aria-hidden', 'true');
						popup.removeAttribute('aria-labelledby');
					}
					if (owner) { owner.setAttribute('aria-expanded', 'false'); owner.removeAttribute('aria-describedby'); owner.focus(); }
					owner = null;
				}
				function show(tip, text) {
					var p = ensurePopup();
					content.textContent = text || '';
					var r = tip.getBoundingClientRect();
					var top = r.bottom + window.scrollY + 8;
					var left = r.left + window.scrollX;
					p.style.top = top + 'px';
					p.style.left = left + 'px';
					p.style.display = 'block';
					p.setAttribute('aria-hidden', 'false');
					owner = tip;
					tip.setAttribute('aria-expanded', 'true');
					tip.setAttribute('aria-describedby', 'read-offline-help-popup');
					// Prevent overflow off the right edge
					var rect = p.getBoundingClientRect();
					var overflowX = rect.right - window.innerWidth + 8;
					if (overflowX > 0) {
						p.style.left = (left - overflowX) + 'px';
					}
					// Focus management
					p.focus();
				}
				document.addEventListener('click', function (e) {
					var tip = e.target.closest ? e.target.closest('.read-offline-help-tip') : null;
					if (tip) {
						e.preventDefault(); e.stopPropagation();
						var text = tip.getAttribute('data-help') || '';
						if (popup && popup.style.display === 'block' && owner === tip) { hide(); return; }
						show(tip, text);
					} else if (popup && popup.contains(e.target)) {
						// clicks inside popup shouldn't close it
						return;
					} else {
						hide();
					}
				});
				// Keyboard activation on tip (Enter/Space)
				document.addEventListener('keydown', function (e) {
					var tip = document.activeElement && document.activeElement.classList && document.activeElement.classList.contains('read-offline-help-tip') ? document.activeElement : null;
					if (!tip) return;
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						var text = tip.getAttribute('data-help') || '';
						if (popup && popup.style.display === 'block' && owner === tip) { hide(); return; }
						show(tip, text);
					}
				});
				// Focus trap (basic): keep Tab cycling between popup and its close button
				document.addEventListener('keydown', function (e) {
					if (!popup || popup.style.display !== 'block') return;
					if (e.key === 'Escape') { hide(); return; }
					if (e.key === 'Tab') {
						var focusables = [popup, closeBtn];
						var idx = focusables.indexOf(document.activeElement);
						if (e.shiftKey) {
							idx = idx <= 0 ? focusables.length - 1 : idx - 1;
						} else {
							idx = idx >= focusables.length - 1 ? 0 : idx + 1;
						}
						e.preventDefault();
						focusables[idx].focus();
					}
				});
				window.addEventListener('scroll', hide, { passive: true });
				window.addEventListener('resize', hide);
			})();
		</script>
		<?php
	}

	// ===== Bulk actions =====
	/**
	 * Register bulk actions for posts.
	 *
	 * @param array $bulk_actions Existing actions.
	 * @return array
	 */
	public static function register_bulk_actions_posts( $bulk_actions ) {
		$bulk_actions[ 'read_offline_export_pdf' ]  = __( 'Export to PDF (Read Offline)', 'read-offline' );
		$bulk_actions[ 'read_offline_export_epub' ] = __( 'Export to EPUB (Read Offline)', 'read-offline' );
		return $bulk_actions;
	}
	/**
	 * Register bulk actions for pages.
	 *
	 * @param array $bulk_actions Existing actions.
	 * @return array
	 */
	public static function register_bulk_actions_pages( $bulk_actions ) {
		$bulk_actions[ 'read_offline_export_pdf' ]  = __( 'Export to PDF (Read Offline)', 'read-offline' );
		$bulk_actions[ 'read_offline_export_epub' ] = __( 'Export to EPUB (Read Offline)', 'read-offline' );
		return $bulk_actions;
	}

	/**
	 * Handle bulk for posts.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction    Action key.
	 * @param array  $post_ids    IDs.
	 * @return string
	 */
	public static function handle_bulk_actions_posts( $redirect_to, $doaction, $post_ids ) {
		return self::handle_bulk_actions( $redirect_to, $doaction, $post_ids, 'post' );
	}
	/**
	 * Handle bulk for pages.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction    Action key.
	 * @param array  $post_ids    IDs.
	 * @return string
	 */
	public static function handle_bulk_actions_pages( $redirect_to, $doaction, $post_ids ) {
		return self::handle_bulk_actions( $redirect_to, $doaction, $post_ids, 'page' );
	}

	/**
	 * Core bulk handler.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction    Action key.
	 * @param array  $post_ids    IDs.
	 * @param string $post_type   Post type.
	 * @return string
	 */
	protected static function handle_bulk_actions( $redirect_to, $doaction, $post_ids, $post_type ) {
		if ( ! in_array( $doaction, array( 'read_offline_export_pdf', 'read_offline_export_epub' ), true ) ) {
			return $redirect_to;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return add_query_arg( array( 'read_offline_error' => 'forbidden' ), $redirect_to );
		}

		$format = $doaction === 'read_offline_export_pdf' ? 'pdf' : 'epub';

		$general_opts = get_option( 'read_offline_settings_general', array() );
		// If the setting key is absent (pre-feature installs), treat it as enabled by default.
		$combine_setting = array_key_exists( 'combine_bulk', (array) $general_opts ) ? ! empty( $general_opts[ 'combine_bulk' ] ) : true;
		$combine         = apply_filters( 'read_offline_bulk_combine', $combine_setting, $post_ids, $format, $post_type );
		$errors          = array();

		if ( $combine && count( $post_ids ) > 1 ) {
			$valid_ids = array();
			foreach ( (array) $post_ids as $pid ) {
				$post = get_post( $pid );
				if ( ! $post || $post->post_type !== $post_type || ! current_user_can( 'read_post', $pid ) ) {
					$errors[] = $pid;
					continue;
				}
				$valid_ids[] = $pid;
			}
			if ( empty( $valid_ids ) ) {
				return add_query_arg( array( 'read_offline_error' => 'no_files' ), $redirect_to );
			}
			$path = Read_Offline_Export::generate_combined( $valid_ids, $format );
			if ( is_wp_error( $path ) || ! $path ) {
				$code = is_wp_error( $path ) ? $path->get_error_code() : 'generation_failed';
				return add_query_arg( array( 'read_offline_error' => $code ), $redirect_to );
			}
			$token = wp_generate_password( 20, false, false );
			set_transient( 'read_offline_file_' . $token, $path, HOUR_IN_SECONDS );
			return add_query_arg(
				array(
					'read_offline_done' => 1,
					'combined'          => 1,
					'count'             => count( $valid_ids ),
					'errors'            => count( $errors ),
					'token'             => $token,
					'format'            => $format,
				),
				$redirect_to
			);
		}

		// Fallback: original per-post generation into ZIP
		$generated = array();
		foreach ( (array) $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== $post_type ) {
				$errors[] = $post_id;
				continue;
			}
			if ( ! current_user_can( 'read_post', $post_id ) ) {
				$errors[] = $post_id;
				continue;
			}
			$path = Read_Offline_Export::generate( $post_id, $format );
			if ( is_wp_error( $path ) || ! $path ) {
				$errors[] = $post_id;
				continue;
			}
			$generated[] = array( 'post_id' => $post_id, 'path' => $path );
		}
		if ( empty( $generated ) ) {
			return add_query_arg( array( 'read_offline_error' => 'no_files' ), $redirect_to );
		}
		$site     = sanitize_title( get_bloginfo( 'name' ) );
		$ts       = current_time( 'Ymd_His' );
		$zip_name = sprintf( '%s_%s_%s.zip', $site, $ts, $format );
		$zip_path = Read_Offline_Export::zip_files( wp_list_pluck( $generated, 'path' ), $zip_name );
		if ( is_wp_error( $zip_path ) || ! $zip_path ) {
			return add_query_arg( array( 'read_offline_error' => 'zip_failed' ), $redirect_to );
		}
		$token = wp_generate_password( 20, false, false );
		set_transient( 'read_offline_zip_' . $token, $zip_path, HOUR_IN_SECONDS );
		return add_query_arg(
			array(
				'read_offline_done' => 1,
				'count'             => count( $generated ),
				'errors'            => count( $errors ),
				'token'             => $token,
				'format'            => $format,
			),
			$redirect_to
		);
	}

	/**
	 * Admin endpoint to download a prepared ZIP.
	 *
	 * @return void
	 */
	public static function download_zip() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to download this file.', 'read-offline' ) );
		}
		$token = isset( $_GET[ 'token' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'token' ] ) ) : '';
		$path  = $token ? get_transient( 'read_offline_zip_' . $token ) : '';
		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( esc_html__( 'The requested file is no longer available.', 'read-offline' ) );
		}
		// Stream file
		$download_name = sanitize_file_name( basename( $path ) );
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path );
		// Optionally delete after download
		@unlink( $path );
		delete_transient( 'read_offline_zip_' . $token );
		exit;
	}

	/**
	 * Admin endpoint to download a combined single file (PDF/EPUB).
	 *
	 * @return void
	 */
	public static function download_combined_file() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to download this file.', 'read-offline' ) );
		}
		$token = isset( $_GET[ 'token' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'token' ] ) ) : '';
		$path  = $token ? get_transient( 'read_offline_file_' . $token ) : '';
		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( esc_html__( 'The requested file is no longer available.', 'read-offline' ) );
		}
		$ext           = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$ct            = ( 'pdf' === $ext ) ? 'application/pdf' : ( 'epub' === $ext ? 'application/epub+zip' : 'application/octet-stream' );
		$download_name = sanitize_file_name( basename( $path ) );
		header( 'Content-Type: ' . $ct );
		header( 'Content-Disposition: attachment; filename="' . $download_name . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path );
		@unlink( $path );
		delete_transient( 'read_offline_file_' . $token );
		exit;
	}

	/**
	 * Clear plugin cache action.
	 *
	 * @return void
	 */
	public static function clear_cache_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'read-offline' ) );
		}
		$nonce = isset( $_POST[ '_roc_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ '_roc_nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'read_offline_clear_cache' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'read-offline' ) );
		}
		if ( class_exists( 'Read_Offline_Export' ) && method_exists( 'Read_Offline_Export', 'clear_cache' ) ) {
			Read_Offline_Export::clear_cache();
		}
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url( 'options-general.php?page=read-offline-settings' );
		}
		$redirect = add_query_arg( 'read_offline_cleared', 1, $redirect );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Display admin notices for this plugin flow.
	 *
	 * @return void
	 */
	public static function admin_notices() {
		if ( isset( $_GET[ 'read_offline_done' ] ) && $_GET[ 'read_offline_done' ] ) {
			$count    = isset( $_GET[ 'count' ] ) ? intval( wp_unslash( $_GET[ 'count' ] ) ) : 0;
			$errors   = isset( $_GET[ 'errors' ] ) ? intval( wp_unslash( $_GET[ 'errors' ] ) ) : 0;
			$token    = isset( $_GET[ 'token' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'token' ] ) ) : '';
			$combined = ! empty( $_GET[ 'combined' ] );
			$action   = $combined ? 'read_offline_download_file' : 'read_offline_download_zip';
			$link     = $token ? esc_url( admin_url( 'admin-post.php?action=' . $action . '&token=' . $token ) ) : '';
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php if ( $combined ) {
					echo esc_html( sprintf( __( 'Read Offline: Combined document created from %1$d post(s). %2$d error(s).', 'read-offline' ), $count, $errors ) );
				} else { /* translators: 1: number of files exported, 2: number of errors */
					echo esc_html( sprintf( __( 'Read Offline: %1$d file(s) exported. %2$d error(s).', 'read-offline' ), $count, $errors ) );
				} ?>
				</p>
				<?php if ( $link ) : ?>
					<p><a class="button button-primary"
							href="<?php echo $link; ?>"><?php echo esc_html( $combined ? __( 'Download file', 'read-offline' ) : __( 'Download ZIP', 'read-offline' ) ); ?></a>
					</p>
				<?php endif; ?>
			</div>
			<?php
		}
		if ( isset( $_GET[ 'read_offline_error' ] ) ) {
			$error   = sanitize_text_field( wp_unslash( $_GET[ 'read_offline_error' ] ) );
			$message = __( 'Read Offline export failed.', 'read-offline' );
			if ( 'forbidden' === $error ) {
				$message = __( 'You do not have permission to export.', 'read-offline' );
			}
			if ( 'no_files' === $error ) {
				$message = __( 'No files were generated.', 'read-offline' );
			}
			if ( 'zip_failed' === $error ) {
				$message = __( 'Failed to create ZIP archive.', 'read-offline' );
			}
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
		if ( isset( $_GET[ 'read_offline_cleared' ] ) && $_GET[ 'read_offline_cleared' ] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Read Offline cache cleared.', 'read-offline' ); ?></p>
			</div>
			<?php
		}
		if ( isset( $_GET[ 'read_offline_reset' ] ) && $_GET[ 'read_offline_reset' ] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Settings were reset to defaults for this tab.', 'read-offline' ); ?></p>
			</div>
			<?php
		}
		if ( isset( $_GET[ 'read_offline_test_done' ] ) && $_GET[ 'read_offline_test_done' ] ) {
			$token = isset( $_GET[ 'token' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'token' ] ) ) : '';
			$data  = $token ? get_transient( 'read_offline_test_' . $token ) : false;
			if ( $data && ! empty( $data[ 'url' ] ) ) {
				$format  = isset( $data[ 'format' ] ) ? strtoupper( $data[ 'format' ] ) : '';
				$post_id = isset( $data[ 'post_id' ] ) ? intval( $data[ 'post_id' ] ) : 0;
				delete_transient( 'read_offline_test_' . $token );
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php
					/* translators: 1: post ID, 2: format (PDF/EPUB) */
					echo esc_html( sprintf( __( 'Test export succeeded for post ID %1$d (%2$s).', 'read-offline' ), $post_id, $format ) );
					?>
					</p>
					<p><a class="button" target="_blank" rel="noopener"
							href="<?php echo esc_url( $data[ 'url' ] ); ?>"><?php esc_html_e( 'Open file', 'read-offline' ); ?></a></p>
				</div>
				<?php
			}
		}
		if ( isset( $_GET[ 'read_offline_test_error' ] ) ) {
			$code     = sanitize_text_field( wp_unslash( $_GET[ 'read_offline_test_error' ] ) );
			$messages = array(
				'forbidden'         => __( 'You do not have permission to run a test export.', 'read-offline' ),
				'invalid_params'    => __( 'Please provide a valid post ID and format.', 'read-offline' ),
				'not_found'         => __( 'The requested post could not be found.', 'read-offline' ),
				'pdf_failed'        => __( 'PDF generation failed. Check the mPDF library and settings.', 'read-offline' ),
				'epub_failed'       => __( 'EPUB generation failed. Check the PHPePub library and settings.', 'read-offline' ),
				'mpdf_missing'      => __( 'mPDF is not available. Install Composer dependencies.', 'read-offline' ),
				'phpepub_missing'   => __( 'PHPePub is not available. Install Composer dependencies.', 'read-offline' ),
				'generation_failed' => __( 'Export failed for unknown reasons.', 'read-offline' ),
			);
			$message  = isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'Test export failed.', 'read-offline' );
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Handle the Test export action in admin.
	 *
	 * @return void
	 */
	public static function test_export_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'read-offline' ) );
		}
		$nonce = isset( $_POST[ '_rot_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ '_rot_nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'read_offline_test_export' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'read-offline' ) );
		}
		$post_id  = isset( $_POST[ 'post_id' ] ) ? intval( wp_unslash( $_POST[ 'post_id' ] ) ) : 0;
		$format   = isset( $_POST[ 'format' ] ) ? sanitize_key( wp_unslash( $_POST[ 'format' ] ) ) : '';
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url( 'options-general.php?page=read-offline-settings' );
		}
		if ( 0 >= $post_id || ! in_array( $format, array( 'pdf', 'epub' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'read_offline_test_error', 'invalid_params', $redirect ) );
			exit;
		}
		$path = Read_Offline_Export::generate( $post_id, $format );
		if ( is_wp_error( $path ) || ! $path ) {
			$code = is_wp_error( $path ) ? $path->get_error_code() : 'generation_failed';
			wp_safe_redirect( add_query_arg( 'read_offline_test_error', $code, $redirect ) );
			exit;
		}
		// Convert path to URL (mirror of Read_Offline_Export::path_to_url)
		$uploads = wp_upload_dir();
		$url     = str_replace( $uploads[ 'basedir' ], $uploads[ 'baseurl' ], $path );
		$token   = wp_generate_password( 20, false, false );
		// Remember last tested ID
		$opts                        = get_option( 'read_offline_settings_general', array() );
		$opts[ 'last_test_post_id' ] = $post_id;
		update_option( 'read_offline_settings_general', $opts );
		set_transient(
			'read_offline_test_' . $token,
			array(
				'url'     => $url,
				'format'  => $format,
				'post_id' => $post_id,
			),
			5 * MINUTE_IN_SECONDS
		);
		wp_safe_redirect(
			add_query_arg(
				array(
					'read_offline_test_done' => 1,
					'token'                  => $token,
				),
				$redirect
			)
		);
		exit;
	}

	/**
	 * Reset settings for a given tab to defaults.
	 *
	 * @return void
	 */
	public static function reset_settings_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'read-offline' ) );
		}
		$nonce = isset( $_POST[ '_ror_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ '_ror_nonce' ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'read_offline_reset_settings' ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'read-offline' ) );
		}
		$tab = isset( $_POST[ 'tab' ] ) ? sanitize_key( wp_unslash( $_POST[ 'tab' ] ) ) : 'general';
		switch ( $tab ) {
			case 'pdf':
				delete_option( 'read_offline_settings_pdf' );
				break;
			case 'epub':
				delete_option( 'read_offline_settings_epub' );
				break;
			case 'general':
			default:
				delete_option( 'read_offline_settings_general' );
				break;
		}
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url( 'options-general.php?page=read-offline-settings&tab=' . $tab );
		}
		$redirect = add_query_arg( 'read_offline_reset', 1, $redirect );
		wp_safe_redirect( $redirect );
		exit;
	}
}
