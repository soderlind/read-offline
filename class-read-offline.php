<?php

class Read_Offline {

	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_version = '0.2.0';
	public static $l10n_domain = "read-offline";
	public static $options;

	public $url;
	public static $temp_root;

	private static $instance;

	public static function get_instance() {

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {

		//self::$plugin_url = plugins_url( basename(dirname(__FILE__)), dirname(__FILE__) );
		self::$plugin_url =  plugin_dir_url( __FILE__ );
		//$this->url = plugin_dir_url( __FILE__ );
		self::$plugin_path = plugin_dir_path( __FILE__ );
		self::$options = get_option( 'Read_Offline_Admin_Settings' );


		$this->_create_tmp_directories();

	}

	public static function query_url($id,$name,$format) {
		//$rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
		if ( get_option('permalink_structure')) {
			return sprintf("%s/read-offline/%s/%s.%s",home_url(),$id,$name,$format);
		} else {
			return sprintf("%s/index.php?read_offline_id=%s&read_offline_name=%s&&read_offline_format=%s",home_url(),$id,$name,$format);
		}
	}

	// from http://php.net/manual/en/function.imagecreatefromjpeg.php#110547
	public static function image_create_frome_image($filepath) {
	    $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize()
	    $allowedTypes = array(
	        1,  // [] gif
	        2,  // [] jpg
	        3,  // [] png
	        6   // [] bmp
	    );
	    if (!in_array($type, $allowedTypes)) {
	        return false;
	    }
	    switch ($type) {
	        case 1 :
	            $im = imageCreateFromGif($filepath);
	        break;
	        case 2 :
	            $im = imageCreateFromJpeg($filepath);
	        break;
	        case 3 :
	            $im = imageCreateFromPng($filepath);
	        break;
	        case 6 :
	            $im = imageCreateFromBmp($filepath);
	        break;
	    }
	    return $im;
	}
	// from http://wordpress.stackexchange.com/a/54629/14546
	// 
	public static function get_excerpt_by_id($post_id){
	    $the_post = get_post($post_id); //Gets post ID
	    $the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
	    $excerpt_length = 35; //Sets excerpt length by word count
	    $the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
	    $words = explode(' ', $the_excerpt, $excerpt_length + 1);

	    if(count($words) > $excerpt_length) :
	        array_pop($words);
	        array_push($words, '…');
	        $the_excerpt = implode(' ', $words);
	    endif;

	    $the_excerpt = '<p>' . $the_excerpt . '</p>';

	    return $the_excerpt;
	}

	private function _create_tmp_directories() {

		// remove old tmp directory, created in v0.2.0
		// global $wp_filesystem;
		// if( $wp_filesystem->is_dir(WP_CONTENT_DIR . '/cache/read-offline-tmp')) {   
		// 	$wp_filesystem->rmdir(WP_CONTENT_DIR . '/cache/read-offline-tmp');
		// }

		if (true === ( wp_mkdir_p(WP_CONTENT_DIR . '/cache/read-offline/tmp'))  && (true == (wp_mkdir_p(WP_CONTENT_DIR . '/cache/read-offline/font') )))  {

			self::$temp_root = WP_CONTENT_DIR . '/cache/read-offline';
		} else {
			return add_action( 'admin_notices', function(){
			    $msg[] = '<div class="error"><p>';
			    $msg[] = '<strong>Read Offline</strong>: ';
			    $msg[] = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?','read-offline' ), WP_CONTENT_DIR . '/cache/read-offline' );
			    $msg[] = '</p></div>';
			    echo implode( PHP_EOL, $msg );
			});
		}
	}

}