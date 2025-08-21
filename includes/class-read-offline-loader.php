<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

class Read_Offline_Loader {
	public static function init() {
		// Load core classes
		require_once __DIR__ . '/class-read-offline-admin.php';
		require_once __DIR__ . '/class-read-offline-frontend.php';
		require_once __DIR__ . '/class-read-offline-export.php';
		// Initialize components
		Read_Offline_Admin::init();
		Read_Offline_Frontend::init();
		Read_Offline_Export::init();
	}
}
