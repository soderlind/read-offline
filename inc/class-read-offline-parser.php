<?php

class Read_Offline_Parser extends Read_Offline {


	private static $instance;

	public static function get_instance() {

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		parent::get_instance();

		add_filter( 'generate_rewrite_rules', array( $this, 'rewrite_rule' ) );
		add_filter( 'query_vars',             array( $this, 'query_vars' ) );
		add_action( 'parse_request',          array( $this, 'parse_request' ) );
		add_filter( 'admin_init',             array( $this, 'flush_rewrite_rules' ) );

		//add_action( 'wp_head',                array($this, 'store_post_styles'),99);
	}

	function rewrite_rule( $wp_rewrite ) {
		$new_rules = array(
		 'read-offline/([^/]+)/([^\.]+).(pdf|epub|mobi|docx|print)$' => sprintf( 'index.php?read_offline_id=%s&read_offline_name=%s&&read_offline_format=%s',$wp_rewrite->preg_index( 1 ),$wp_rewrite->preg_index( 2 ),$wp_rewrite->preg_index( 3 ) ),
		);

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		return $wp_rewrite->rules;
	}

	function query_vars( $query_vars ) {
		$query_vars[] = 'read_offline_id';
		$query_vars[] = 'read_offline_name';
		$query_vars[] = 'read_offline_format';
		$query_vars[] = 'read_offline_nonce';
		return $query_vars;
	}

	function parse_request( $wp_query ) {
		global $post;
		$create_new = true;
		if ( '1' == parent::$options['misc']['cache'] ) {
			$create_new = false;
		}
		if ( isset( $wp_query->query_vars['read_offline_id'] ) ) {
			$docformat = strtolower( $wp_query->query_vars['read_offline_format'] );
			$filename = sprintf( '%s.%s',$wp_query->query_vars['read_offline_name'],$wp_query->query_vars['read_offline_format'] );
			$post_id = $wp_query->query_vars['read_offline_id'];
			$post = get_page( $post_id );
			if ( is_object( $post ) && 'publish' == $post->post_status ) {
				if ( isset( $_REQUEST['read-offline-code'] ) ) {
					$code  = $_REQUEST['read-offline-code'];
					if ( base64_encode( AUTH_KEY ) == $code ) {
						$create_new = true;
					}
				}

				if ( false == $create_new && 'print' != $docformat ) {
					$mime_type = parent::$mime_types[ $docformat ];
					$attachments = new WP_Query( array(
						'post_type'      => 'attachment',
						'post_status'    => 'any',
						'posts_per_page' => 500,
						'post_parent'    => $post_id,
						'post_mime_type' => $mime_type,
					) );

					foreach ( $attachments->posts as $attachment ) {
						$attached_file = get_attached_file( $attachment->ID, true );
						$attached_url  = wp_get_attachment_url( $attachment->ID );
						if ( 0 == strpos( basename( $attached_file, '.' . $docformat ), $post->post_name ) ) { // strpos 0 = start of string
							if ( filesize( $attached_file ) > 0 ) {
								parent::read_offline_download( $attached_file,$mime_type,wp_create_nonce( 'read-offline-download' ) );
								exit();
							}
						}
					}
					$create_new = true;
				}

				if ( true == $create_new || 'print' == $docformat ) {
					$create = Read_Offline_Create::get_instance();
					$create->init( $post );

					switch ( $docformat ) {
						case 'pdf':
							$create->pdf( $post );
							break;
						case 'epub':
							$create->epub( $post );
							break;
						case 'mobi':
							$create->mobi( $post );
							break;
						case 'print':
							$create->pprint( $post );
							break;
					}
					exit();
				}
			}
		}
	}


	function flush_rewrite_rules() {
		$rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
		if ( ! isset( $rules['read-offline/([^/]+)/([^\.]+).(pdf|epub|mobi)$'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
	}
}
