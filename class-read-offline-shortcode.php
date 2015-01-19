<?php

class Read_Offline_Shortcode {


	private static $instance;


	public static function get_instance() {

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {

	}


/*

	public function __set($name, $value){
	     throw new Exception("Variable ".$name." has not been set.", 1);
	}

	public function __get($name){
	     throw new Exception("Variable ".$name." has not been declared and can not be get.", 1);
	}
*/
}