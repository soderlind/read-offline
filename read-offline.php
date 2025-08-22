<?php
/**
 * Plugin bootstrap for Read Offline.
 *
 * @wordpress-plugin
 * Plugin Name:       Read Offline
 * Description:       Export posts and pages to PDF, EPUB, and Markdown for offline reading or reuse.
 * Version:           0.2.3
 * Author:            Your Name
 * Text Domain:       read-offline
 * Requires at least: 6.5
 * Requires PHP:      8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer autoload if available
$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
} elseif ( file_exists( __DIR__ . '/includes/autoload-fallback.php' ) ) {
	// If no Composer autoload, register a lightweight fallback for bundled libs
	require_once __DIR__ . '/includes/autoload-fallback.php';
}

// Load polyfills for vendor libs (e.g., PHPePub on PHP 8+)
require_once __DIR__ . '/includes/phpepub-polyfills.php';

// Autoload classes
require_once __DIR__ . '/includes/class-read-offline-loader.php';

// Initialize plugin
add_action( 'plugins_loaded', [ 'Read_Offline_Loader', 'init' ] );
