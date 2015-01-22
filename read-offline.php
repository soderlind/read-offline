<?php
/*
Plugin Name: Read Offline
Plugin URI: http://soderlind.no/archives/2012/10/01/read-offline/
Description: lorem ipsum dolores est
Author: Per Soderlind
Version: 0.2.2
Author URI: http://soderlind.no
Text Domain: read-offline
Domain Path: /languages
*/



if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
    return add_action( 'admin_notices', 'read_offline_admin_notice_php_version' );
}



Read_Offline_Loader::autoload(__DIR__);

if ( is_admin() ) {
 	new Read_Offline_Admin_Settings ();
}
if (get_option( 'Read_Offline_Admin_Settings' )) {
	add_action( 'init', function(){
			//Read_Offline::get_instance();
			Read_Offline_Parser::get_instance();
			//Read_Offline_Shortcode::get_instance();
			Read_Offline_UX::get_instance();

	});
	add_action( 'widgets_init', function(){
	     register_widget( 'Read_Offline_Widget' );
	});
} else {
	if ( is_admin() ) {
		return add_action( 'admin_notices', 'read_offline_admin_notice_update_options' );
	}
}

/**
 * Load language file
 */
add_action('plugins_loaded', function(){
	load_plugin_textdomain( 'read-offline', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
});


function read_offline_admin_notice_php_version () {
    $msg[] = '<div class="error"><p>';
    $msg[] = 'Please upgarde PHP at least to version 5.3.0<br>';
    $msg[] = 'Your current PHP version is <strong>' . PHP_VERSION . '</strong>, which is not suitable for plugin <strong>Read Offline</strong>.';
    $msg[] = '</p></div>';
    echo implode( PHP_EOL, $msg );
}

function read_offline_admin_notice_update_options () {
    $msg[] = '<div class="error"><p>';
    $msg[] = '<strong>Read Offline</strong>:';
    $msg[] = 'Please save your <a href="admin.php?page=read_offline_options">Read Offline options</a> ';
    $msg[] = '</p></div>';
    echo implode( PHP_EOL, $msg );
}

/**
 *
 */
class Read_Offline_Loader {
	private static  $dir = __DIR__;

	public static function autoload($dir = '' ) {
		if ( ! empty( $dir ) )
			self::$dir = $dir;

		spl_autoload_register(  __CLASS__ . '::loader'  );
	}

	private static function loader( $class_name ) {
		$class_path = trailingslashit(self::$dir) . 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		if ( file_exists( $class_path ) )
			require_once $class_path;
	}
}

