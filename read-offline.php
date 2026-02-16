<?php
/**
 * Plugin bootstrap for Read Offline.
 *
 * @package ReadOffline
 * @wordpress-plugin
 * Plugin Name:       Read Offline
 * Description:       Export posts and pages to PDF, EPUB, and Markdown for offline reading or reuse.
 * Version:           2.3.0
 * Author:            Per Soderlind
 * Text Domain:       read-offline
 * Requires at least: 6.5
 * Requires PHP:      8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Queue-aware REST hooks (safe to include even if not used).
if ( file_exists( __DIR__ . '/includes/queue-hooks.php' ) ) {
	require_once __DIR__ . '/includes/queue-hooks.php';
}
// Load Composer autoload if available.
$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
} elseif ( file_exists( __DIR__ . '/includes/autoload-fallback.php' ) ) {
	// If no Composer autoload, register a lightweight fallback for bundled libs.
	require_once __DIR__ . '/includes/autoload-fallback.php';
}

// Include the generic updater class.
if ( ! class_exists( 'Soderlind\WordPress\GitHub_Plugin_Updater' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-github-plugin-updater.php';
}
// Initialize the updater with configuration.
$read_offline_updater = \Soderlind\WordPress\GitHub_Plugin_Updater::create_with_assets(
	'https://github.com/soderlind/read-offline',
	__FILE__,
	'read-offline',
	'/read-offline\.zip/',
	'main'
);

// Load polyfills for vendor libs (e.g., PHPePub on PHP 8+).
require_once __DIR__ . '/includes/phpepub-polyfills.php';

// Autoload classes.
require_once __DIR__ . '/includes/class-read-offline-loader.php';

// Initialize plugin.
add_action( 'plugins_loaded', array( 'Read_Offline_Loader', 'init' ) );
