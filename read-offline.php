<?php
/*
Plugin Name: Read Offline
Plugin URI: http://soderlind.no/archives/2012/10/01/read-offline/
Description: Read Offline allows you to download or print posts and pages. You can download the posts as PDF, ePub or mobi
Author: Per Soderlind
Version: 0.9.17
Author URI: http://soderlind.no
Text Domain: read-offline
Domain Path: /languages
*/
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'READOFFLINE_PATH', __DIR__ );
define( 'READOFFLINE_URL', plugin_dir_url( __FILE__ ) );
define( 'READOFFLINE_CACHE', WP_CONTENT_DIR . '/cache/read-offline' );
define( 'READOFFLINE_VERSION', '0.9.17' );


if ( version_compare( PHP_VERSION, '7.3.0' ) < 0 ) {
	return add_action( 'admin_notices', 'read_offline_admin_notice_php_version' );
} /*
 elseif ( version_compare( PHP_VERSION, '5.6.0' ) < 0 ) {
	add_action( 'admin_notices', 'read_offline_admin_notice_php_warning' );
}*/




// load epub library
require_once READOFFLINE_PATH . '/vendor/autoload.php';
// autoload inc/class.*.php files
Read_Offline_Loader::autoload( READOFFLINE_PATH . '/inc' );

if ( is_admin() ) {
		new Read_Offline_Admin_Settings();
}

if ( get_option( 'Read_Offline_Admin_Settings' ) ) {
	add_action(
		'init',
		function() {
			// Read_Offline::get_instance();
			Read_Offline_Parser::get_instance();
			Read_Offline_Shortcode::get_instance();
			Read_Offline_UX::get_instance();
		},
		1
	);
	// add_action( 'widgets_init', function(){
	// register_widget( 'Read_Offline_Widget' );
	// });
} else {
	if ( is_admin() ) {
		return add_action( 'admin_notices', 'read_offline_admin_notice_update_options', 99 );
	}
}

/**
 * Load language file
 */
add_action(
	'plugins_loaded',
	function() {
		load_plugin_textdomain( 'read-offline', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);


function read_offline_admin_notice_php_version() {
	$msg[] = '<div class="notice notice-error"><p>';
	$msg[] = '<strong>Read Offline</strong>: Your current PHP version is <strong>' . PHP_VERSION . '</strong>, please upgarde PHP at least to version 5.3 (PHP 5.6 or greater is reccomended). ';
	$msg[] = '<a href="https://wordpress.org/about/requirements/">Ask</a> your hosting provider for an upgrade';
	$msg[] = '</p></div>';
	echo implode( PHP_EOL, $msg );
}

function read_offline_admin_notice_php_warning() {
	$msg[] = '<div class="notice notice-warning is-dismissible "><p>';
	$msg[] = '<strong>Read Offline</strong>: Your current PHP version is ' . PHP_VERSION . '. <strong>Read Offline</strong> runs best on PHP 5.6 or greater.';
	$msg[] = '<a href="https://wordpress.org/about/requirements/">Ask</a> your hosting provider for an upgrade';
	$msg[] = '</p></div>';
	echo implode( PHP_EOL, $msg );
}


function read_offline_admin_notice_update_options() {
	$msg[] = '<div class="updated"><p>';
	// $msg[] = '<strong>Read Offline</strong>:';
	$msg[] = __( 'Please configure', 'read-offline' ) . ' <a href="admin.php?page=read_offline_options"><strong>Read Offline</strong></a> ';
	$msg[] = '</p></div>';
	echo implode( PHP_EOL, $msg );
}

/**
 *
 */
class Read_Offline_Loader {
	private static $dir = __DIR__;

	public static function autoload( $dir = '' ) {
		if ( ! empty( $dir ) ) {
			self::$dir = $dir;
		}
		spl_autoload_register( __CLASS__ . '::loader' );
	}

	private static function loader( $class_name ) {

		$filename = sprintf( 'class-%s.php', strtolower( str_replace( '_', '-', $class_name ) ) );

		$class_path = trailingslashit( self::$dir ) . $filename;

		if ( file_exists( $class_path ) ) {
			require_once $class_path;
		}

	}
}
