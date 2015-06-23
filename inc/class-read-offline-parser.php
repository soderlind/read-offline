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

		add_filter( 'generate_rewrite_rules', array($this, 'rewrite_rule'));
		add_filter( 'query_vars',             array($this, 'query_vars'));
		add_action( 'parse_request',          array($this, 'parse_request'));
		add_filter( 'admin_init',             array($this, 'flush_rewrite_rules'));




		//add_action( 'wp_head',                array($this, 'store_post_styles'),99);
	}

		function rewrite_rule( $wp_rewrite ) {
			$new_rules = array(
				 'read-offline/([^/]+)/([^\.]+).(pdf|epub|mobi|docx|print)$' => sprintf("index.php?read_offline_id=%s&read_offline_name=%s&&read_offline_format=%s",$wp_rewrite->preg_index(1),$wp_rewrite->preg_index(2),$wp_rewrite->preg_index(3))
			);

			$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    		return $wp_rewrite->rules;
		}

		function query_vars( $query_vars ) {
			$query_vars[] = 'read_offline_id';
			$query_vars[] = 'read_offline_name';
			$query_vars[] = 'read_offline_format';
			return $query_vars;
		}

		function parse_request($wp_query) {
			global $post;
			if (isset($wp_query->query_vars['read_offline_id'])) {
				$docformat = strtolower($wp_query->query_vars['read_offline_format']);
				$id = $wp_query->query_vars['read_offline_id'];
				$post = get_page($id);
				if (is_object($post) && $post->post_status == 'publish') {

					$create = Read_Offline_Create::get_instance();
					$create->init($post);

					switch ($docformat) {
						case 'pdf':
							$create->pdf($post);
							break;
						case 'epub':
							$create->epub($post);
							break;
						case 'mobi':
							$create->mobi($post);
							break;
						case 'print':
							$create->pprint($post);
							break;
					}
					exit();
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



		// function store_post_styles() {
		// 	global $wp_styles, $post;

		// 	if (is_single()) {
		// 		$transient_id = 'read_offline_post_styles_' . $post->ID;
		// 		$transient = get_transient( $transient_id );
		// 		if ( empty( $transient ) ) {
		// 			$css_sources = array();
		// 			$styles = $wp_styles->done;
		// 			foreach ( $styles as $loaded_styles ) {
		// 				$css_sources[] = $wp_styles->registered[$loaded_styles]->src;
		// 			}
		// 			//printf("<pre>%s</pre>%s",print_r($css_sources,true), $transient_id);
		// 		   set_transient($transient_id , ($css_sources), 60 ); // ex. sec*min*hours 60*60*12 is 12 hours
		// 		}
		// 	}
		// }

}
