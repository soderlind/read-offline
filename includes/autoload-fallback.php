<?php
/**
 * Lightweight PSR-4 fallback autoloader for bundled vendor libraries.
 *
 * Used only when vendor/autoload.php is not present. This enables the plugin
 * to work out of the box if the vendor directory is bundled with the plugin
 * release without requiring a composer install on the target site.
 *
 * @package Read_Offline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Read_Offline_Psr4_Fallback' ) ) {
	return;
}

class Read_Offline_Psr4_Fallback {
	/** @var array<string,string> */
	protected static $prefixes = array();

	/**
	 * Register the autoloader with a fixed set of prefixes for our bundled libs.
	 *
	 * @return void
	 */
	public static function register() {
		// Base plugin dir
		$base = dirname( __DIR__ );
		// Map namespace prefixes to their src directories
		self::$prefixes = array(
			'Mpdf\\'               => $base . '/vendor/mpdf/mpdf/src/',
			'PHPePub\\'            => $base . '/vendor/grandt/phpepub/src/',
			'setasign\\Fpdi\\'     => $base . '/vendor/setasign/fpdi/src/',
			'Psr\\Log\\'           => $base . '/vendor/psr/log/src/',
			'Psr\\Http\\Message\\' => $base . '/vendor/psr/http-message/src/',
			'MyCLabs\\DeepCopy\\'  => $base . '/vendor/myclabs/deep-copy/src/',
		);
		spl_autoload_register( array( __CLASS__, 'autoload' ), true, true );
	}

	/**
	 * PSR-4 style autoload callback.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	protected static function autoload( $class ) {
		foreach ( self::$prefixes as $prefix => $dir ) {
			$len = strlen( $prefix );
			if ( 0 !== strncmp( $class, $prefix, $len ) ) {
				continue;
			}
			$relative = substr( $class, $len );
			$file     = $dir . str_replace( '\\', '/', $relative ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
			return;
		}
	}
}

Read_Offline_Psr4_Fallback::register();
