<?php

class Read_Offline {


	public static $options;

	public static $mime_types = array (
			'epub' => 'application/epub+zip',
			'mobi' => 'application/x-mobipocket-ebook',
			'pdf'  => 'application/pdf'
		);

	private static $instance;

	public static function get_instance() {

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {

		self::$options = get_option( 'Read_Offline_Admin_Settings' );
		if (is_admin()) {
			add_action( 'admin_init', array($this, 'read_offline_update' ));
		}
		add_filter('upload_mimes', array($this,'add_epub_mobi_pdf_mime_types'), 1, 1);

		$post_types = array_keys(array_intersect_assoc(
			array(
				 'post' => 1
				,'page' => 1
			)
			, self::$options['where']['post_types']
		));

		foreach ($post_types as $post_type)  {
			add_action('save_post_' . $post_type, array($this,'save_as_attachment_to_post_type'),10,2);
		}
//		add_action('save_post', array($this,'save_as_attachment_to_post_type'));
	}

	public static function query_url($id,$name,$format, $refresh = false) {
		//$rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
		if ( get_option('permalink_structure')) {
			return sprintf("%s/read-offline/%s/%s.%s%s",
				home_url(),$id,$name,$format, 
				( $refresh ) ? '?nonce=' . wp_create_nonce( 'read-offline' ) : ''
			);
		} else {
			return sprintf("%s/index.php?read_offline_id=%s&read_offline_name=%s&&read_offline_format=%s%s",
				home_url(),$id,$name,$format,
				( $refresh ) ? '&nonce=' . wp_create_nonce( 'read-offline' ) : ''
			);
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


	public function save_as_attachment_to_post_type($post_id,$post) {
		// Autosave, do nothing
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		        return $post_id;
		// AJAX? Not used here
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		        return $post_id;
		// Check user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) )
		        return $post_id;
		// Return if it's a post revision
		if ( false !== wp_is_post_revision( $post_id ) )
		        return $post_id;
		//  a newly created post, with no content
		if ( 'auto-draft' == get_post_status ( $post_id ))
				return $post_id;
		if ( isset($_GET['action']) && 'trash' == strtolower($_GET['action']))
				return $post_id;

		foreach (self::$mime_types as $file_extention => $mime_type) {
			$is_previously_attached = false;
			$attachments = get_posts( array(
				'post_type' => 'attachment',
				'posts_per_page' => -1,
				'post_parent' => $post_id,
				'post_mime_type' => $mime_type
			) );
			$readoffline_url = self::query_url($post_id,$post->post_name,$file_extention);
			// previously attached ?
			foreach ($attachments as $attachment) {
				$attached_file = get_attached_file( $attachment->ID, true);
				$attached_url  = wp_get_attachment_url( $attachment->ID );
				if ($post->post_name == basename($attached_file, '.' . $file_extention)) {
					$is_previously_attached = true;
					wp_get_http($readoffline_url,$attached_file);
					update_attached_file( $attachment_id, $attached_file );
				}
			}

			//not previously attached
			if (false == $is_previously_attached) {
				//create a new file
				$to_filename = sprintf('%s.%s',$post->post_name,$file_extention);
				$uploaded_bits = wp_upload_bits($to_filename,null,file_get_contents($readoffline_url));
				if ( false !== $uploaded_bits['error'] ) {
					$error = $uploaded_bits['error'];
        			return add_action( 'admin_notices', function() use ( $error ){
						    $msg[] = '<div class="error"><p>';
						    $msg[] = '<strong>Read Offline</strong>: ';
						    $msg[] = sprintf( __( 'wp_upload_bits failed,  error: "<strong>%s</strong>','read-offline' ), $error );
						    $msg[] = '</p></div>';
						    echo implode( PHP_EOL, $msg );
						});
      			}
				$attached_file = $uploaded_bits['file'];
				$attached_url  = $uploaded_bits['url'];

				// code from: http://codex.wordpress.org/Function_Reference/wp_insert_attachment#Example
				// Prepare an array of post data for the attachment.
				$attachment = array(
					'guid'           => $attached_url, 
					'post_mime_type' => $mime_type,
					'post_title'     => $post->post_title,
					'post_content'   => wp_strip_all_tags(self::get_excerpt_by_id($post_id)),
					'post_status'    => 'inherit'
				);

				// Insert the attachment.
				$attach_id = wp_insert_attachment( $attachment, $attached_file, $post_id );

				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata( $attach_id, $attached_file );
				wp_update_attachment_metadata( $attach_id, $attach_data );
			}
		}

	}



	function add_epub_mobi_pdf_mime_types($mime_types) {

	    foreach (self::$mime_types as $file_extention => $mime_type) {
	    	$mime_types[$file_extention] = $mime_type;
	    }
	    return $mime_types;
	}




	public function read_offline_update() {

		$options = get_option( "Read_Offline" );
		$version = (isset($options['version'])) ? $options['version'] : '0';

		if ( $version != READOFFLINE_VERSION ) {
			$options['version'] = READOFFLINE_VERSION;

			$this->_remove_tmp_directories();

			update_option( "Read_Offline", $options );
		}
		$this->_create_tmp_directories();
	}

	private function _create_tmp_directories() {
		global $wp_filesystem;
		if( ! $wp_filesystem || ! is_object($wp_filesystem) )
			WP_Filesystem();
		if( ! is_object($wp_filesystem) )
			wp_die('WP_Filesystem Error:' . print_r($wp_filesystem,true));

		$directories = array('cache/read-offline/tmp', 'cache/read-offline/font');
		foreach ($directories as $directory) {
			$path = WP_CONTENT_DIR;
			foreach (explode('/', $directory) as $foldername) {
				$path .= '/' . $foldername;
				if ( !$wp_filesystem->exists($path) ) {
					if ( !$wp_filesystem->mkdir($path, FS_CHMOD_DIR) ){
						return add_action( 'admin_notices', function() use ( $path ){
						    $msg[] = '<div class="error"><p>';
						    $msg[] = '<strong>Read Offline</strong>: ';
						    $msg[] = sprintf( __( 'Unable to create directory "<strong>%s</strong>". Is its parent directory writable by the server?','read-offline' ), $path );
						    $msg[] = '</p></div>';
						    echo implode( PHP_EOL, $msg );
						});
					}
				}
			}
		}
	}


	private function _remove_tmp_directories() {
		global $wp_filesystem;
		if( ! $wp_filesystem || ! is_object($wp_filesystem) )
			WP_Filesystem();
		if( ! is_object($wp_filesystem) )
			wp_die('WP_Filesystem Error:' . print_r($wp_filesystem,true));

		$directories = array(WP_CONTENT_DIR . '/cache/read-offline', WP_CONTENT_DIR . '/cache/read-offline-tmp');
		foreach ($directories as $directory) {
			if (file_exists($directory)) {
				if (true !== $wp_filesystem->rmdir( $directory , true )) {
				    return add_action( 'admin_notices', function() use ( $directory ){
						    $msg[] = '<div class="error"><p>';
						    $msg[] = '<strong>Read Offline</strong>: ';
						    $msg[] = sprintf( __( 'Unable to remove cache directory "<strong>%s</strong>". Is it and its directories writable by the server?','read-offline' ), $directory );
						    $msg[] = '</p></div>';
						    echo implode( PHP_EOL, $msg );
						});
				}
			}
		}
	}


}