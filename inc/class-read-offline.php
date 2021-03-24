<?php

if ( ! defined( 'FS_CHMOD_DIR' ) ) {
	define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );
}
	
class Read_Offline {

	public static $options;

	public static $mime_types = array();

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

		// Only generate files for formats selected in plugin settings
		if( (bool) self::$options['what']['formats']['epub'] ) {
			self::$mime_types['epub'] = 'application/epub+zip';
		}
		if( (bool) self::$options['what']['formats']['mobi'] ) {
			self::$mime_types['mobi'] = 'application/x-mobipocket-ebook';
		}
		if( (bool) self::$options['what']['formats']['pdf'] ) {
			self::$mime_types['pdf'] = 'application/pdf';
		}
		
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'read_offline_update' ) );
		}
		add_filter( 'upload_mimes', array( $this, 'add_epub_mobi_pdf_mime_types' ), 1, 1 );
		if ( is_multisite() ) {
			// add pdf, epub and mobi to Upload file types in wp-admin/network/settings.php
			$new_upload_filetypes = implode(' ',
				array_unique(array_merge(
					explode( ' ', get_site_option( 'upload_filetypes' ) ),
					array_keys( self::$mime_types )
				))
			);
			update_site_option( 'upload_filetypes', $new_upload_filetypes );
		}

		$post_types = array_keys(array_intersect(
			self::$options['where']['post_types'],
			array(
				 'post' => 1,
				 'page' => 1,
			)
		));

		if ( '1' == self::$options['misc']['cache'] ) {
			foreach ( $post_types as $post_type ) {
				add_action( 'save_post_' . $post_type, array( $this, 'save_as_attachment_to_post_type' ),10,2 );
			}
		}
	}

	public static function query_url( $post_id, $name, $format, $refresh = false ) {
		$code = base64_encode( AUTH_KEY );
		if ( get_option( 'permalink_structure' ) ) {
			return sprintf('%s/read-offline/%s/%s.%s%s',
				site_url(),$post_id,$name,$format,
				( $refresh ) ? '?read-offline-code=' . $code  : ''
			);
		} else {
			return sprintf('%s/index.php?read_offline_id=%s&read_offline_name=%s&&read_offline_format=%s%s',
				site_url(),$post_id,$name,$format,
				( $refresh ) ? '&read-offline-code=' . $code  : ''
			);
		}
	}

	// from http://php.net/manual/en/function.imagecreatefromjpeg.php#110547
	public static function image_create_frome_image( $filepath ) {
	    $type = exif_imagetype( $filepath ); // [] if you don't have exif you could use getImageSize()
	    $allowed_types = array(
	        1,  // [] gif
	        2,  // [] jpg
	        3,  // [] png
	        6,   // [] bmp
	    );
	    if ( ! in_array( $type, $allowed_types ) ) {
	        return false;
	    }
	    switch ( $type ) {
	        case 1 :
	            $im = imageCreateFromGif( $filepath );
	        break;
	        case 2 :
	            $im = imageCreateFromJpeg( $filepath );
	        break;
	        case 3 :
	            $im = imageCreateFromPng( $filepath );
	        break;
	        case 6 :
	            $im = imageCreateFromBmp( $filepath );
	        break;
	    }
	    return $im;
	}
	// from http://wordpress.stackexchange.com/a/54629/14546
	//
	public static function get_excerpt_by_id( $post_id ) {
	    $the_post = get_post( $post_id ); //Gets post ID
	    $the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
	    $excerpt_length = 35; //Sets excerpt length by word count
	    $the_excerpt = strip_tags( strip_shortcodes( $the_excerpt ) ); //Strips tags and images
	    $words = explode( ' ', $the_excerpt, $excerpt_length + 1 );

	    if ( count( $words ) > $excerpt_length ) :
	        array_pop( $words );
	        array_push( $words, '…' );
	        $the_excerpt = implode( ' ', $words );
	    endif;

	    $the_excerpt = '<p>' . $the_excerpt . '</p>';

	    return $the_excerpt;
	}


	public function save_as_attachment_to_post_type( $post_id, $post ) {

		// Autosave, do nothing
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		        return $post_id; }
		// AJAX? Not used here
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		        return $post_id; }
		// Check user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
		        return $post_id; }
		// Return if it's a post revision
		if ( false !== wp_is_post_revision( $post_id ) ) {
		        return $post_id; }
		//  a newly created post, with no content
		if ( 'auto-draft' == get_post_status( $post_id ) ) {
				return $post_id; }
		if ( isset( $_GET['action'] ) && 'trash' == strtolower( $_GET['action'] ) ) {
				return $post_id; }

		foreach ( self::$mime_types as $file_extention => $mime_type ) {
			$is_previously_attached = false;

			$attachments = new WP_Query( array(
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'posts_per_page' => 500,
				'post_parent'    => $post_id,
				'post_mime_type' => $mime_type,
			) );
			$readoffline_url = self::query_url( $post_id,$post->post_name,$file_extention,true );

			// previously attached?  delete it !
			foreach ( $attachments->posts as $attachment ) {
				$attached_file = get_attached_file( $attachment->ID, true );
				$attached_url  = wp_get_attachment_url( $attachment->ID );
				if ( 0 == strpos( basename( $attached_file, '.' . $file_extention ), $post->post_name ) ) { // strpos 0 = start of string
					wp_delete_attachment( $attachment->ID, true );
				}
			}

			//create a new attachment
			$to_filename = sprintf( '%s.%s',$post->post_name,$file_extention );
			$uploaded_bits = wp_upload_bits(
				$to_filename,
				null, //deprecated
				file_get_contents( $readoffline_url ), // replace with wp_remote_get
				date_i18n( 'Y/m', strtotime( $post->post_date ) ) // save in post date yyyy/mm folder
			);
			if ( false !== $uploaded_bits['error'] ) {
				$error = $uploaded_bits['error'];
				return add_action( 'admin_notices', function() use ( $error ) {
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
				'post_content'   => wp_strip_all_tags( self::get_excerpt_by_id( $post_id ) ),
				'post_status'    => 'inherit',
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

	function add_epub_mobi_pdf_mime_types( $mime_types ) {

	    foreach ( self::$mime_types as $file_extention => $mime_type ) {
	    	$mime_types[ $file_extention ] = $mime_type;
	    }
	    return $mime_types;
	}


	function read_offline_download( $attached_file, $mime_type, $nonce ) {
		if ( wp_verify_nonce( $nonce, 'read-offline-download' ) ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Cache-Control: public, must-revalidate, max-age=0' );
			header( 'Pragma: public' );
			header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
			header( 'Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT' );
			header( 'Content-Type: application/force-download' );
			header( 'Content-Type: application/octet-stream', false );
			header( 'Content-Type: application/download', false );
			header( 'Content-Type: ' . $mime_type , false );
			if ( ! isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) or empty( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
				// don't use length if server using compression
				header( 'Content-Length: '.filesize( $attached_file ) );
			}
			header( 'Content-disposition: attachment; filename="'. basename( $attached_file ) .'"' );
			readfile( $attached_file );
			exit();
		}
	}


	public function read_offline_update() {

		$options = get_option( 'Read_Offline' );
		$version = (isset( $options['version'] )) ? $options['version'] : '0';

		if ( READOFFLINE_VERSION !== $version ) {
			$options['version'] = READOFFLINE_VERSION;

			if ( false === isset( self::$options['misc']['cache'] ) ) {
				self::$options['misc']['cache'] = 0;
				update_option( 'Read_Offline_Admin_Settings', self::$options );
			}
			if ( false === isset( self::$options['mobi']['add_toc'] ) ) {
				self::$options['mobi']['add_toc'] = 0;
				self::$options['mobi']['toc'] = 0;
				update_option( 'Read_Offline_Admin_Settings', self::$options );
			}
			if ( false === isset( self::$options['epub']['add_toc'] ) ) {
				self::$options['epub']['add_toc'] = 0;
				self::$options['epub']['toc'] = 0;
				update_option( 'Read_Offline_Admin_Settings', self::$options );
			}
			$this->_remove_tmp_directories();
			update_option( 'Read_Offline', $options );
		}
		$this->_create_tmp_directories();
	}

	private function _create_tmp_directories() {
		global $wp_filesystem;
		if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
			WP_Filesystem(); }
		if ( ! is_object( $wp_filesystem ) ) {
			wp_die( 'WP_Filesystem Error:' . print_r( $wp_filesystem,true ) ); }

		$directories = array( 'cache/read-offline/tmp', 'cache/read-offline/font' );
		foreach ( $directories as $directory ) {
			$path = WP_CONTENT_DIR;
			foreach ( explode( '/', $directory ) as $foldername ) {
				$path .= '/' . $foldername;
				if ( ! $wp_filesystem->exists( $path ) ) {
					if ( ! $wp_filesystem->mkdir( $path, FS_CHMOD_DIR ) ) {
						return add_action( 'admin_notices', function() use ( $path ) {
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
		if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
			WP_Filesystem(); }
		if ( ! is_object( $wp_filesystem ) ) {
			wp_die( 'WP_Filesystem Error:' . print_r( $wp_filesystem,true ) ); }

		$directories = array( WP_CONTENT_DIR . '/cache/read-offline', WP_CONTENT_DIR . '/cache/read-offline-tmp' );
		foreach ( $directories as $directory ) {
			if ( file_exists( $directory ) ) {
				if ( true !== $wp_filesystem->rmdir( $directory , true ) ) {
				    return add_action( 'admin_notices', function() use ( $directory ) {
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
