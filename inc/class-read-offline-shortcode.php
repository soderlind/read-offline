<?php

class Read_Offline_Shortcode extends Read_Offline {


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

		add_shortcode( 'pdf', array( $this, 'pdf' ) );
		add_shortcode( 'epub', array( $this, 'epub' ) );
		add_shortcode( 'mobi', array( $this, 'mobi' ) );
		add_shortcode( 'print', array( $this, 'prnt' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'shortcode_script_style' ) );
	}

	function pdf( $attributes, $content ) {
		global $post;
		if ( ! is_object( $post ) ) { return; }

		$attributes = shortcode_atts( array(// default values
			'text'     => __( 'Download PDF',  'read-offline' ),
			'icon'     => 'true',
		), $attributes );

		return $this->_create_output( 'pdf' , $attributes, $post );
	}

	function epub( $attributes, $content ) {
		global $post;
		if ( ! is_object( $post ) ) { return; }

		$attributes = shortcode_atts( array(// default values
			'text'     => __( 'Download ePub',  'read-offline' ),
			'icon'     => 'true',
		), $attributes );

		return $this->_create_output( 'epub', $attributes, $post );
	}

	function mobi( $attributes, $content ) {
		global $post;
		if ( ! is_object( $post ) ) { return; }

		$attributes = shortcode_atts( array(// default values
			'text'     => __( 'Download mobi',  'read-offline' ),
			'icon'     => 'true',
		), $attributes );

		return $this->_create_output( 'mobi', $attributes, $post );
	}

	function prnt( $attributes, $content ) {
		global $post;
		if ( ! is_object( $post ) ) { return; }

		$attributes = shortcode_atts( array(// default values
			'text'     => __( 'Print',  'read-offline' ),
			'icon'     => 'true',
		), $attributes );

		return $this->_create_output( 'print', $attributes, $post );
	}


	private function _create_output( $type, $attributes, $post ) {
		$post_types = array_keys(array_intersect(
			parent::$options['where']['post_types'],
			array(
				 'post' => 1,
				 'page' => 1,
			)
		));
		if ( ! is_singular( $post_types ) ) {
			return '';
		}
		$readoffline = sprintf('<div class="readoffline-shortcode"><a class="%s" href="%s" title="%s %s.%s" %s>%s</a></div>%s',
			( 'true' != $attributes['icon'] ) ? 'noicon' : $type
			, parent::query_url( $post->ID,$post->post_name,$type )
			, ('print' == $type) ? __( 'Print','read-offline' ) : __( 'Download','read-offline' )
			, $post->post_name
			, ('print' == $type) ? '' : $type
			, ('print' == $type) ? 'onclick="read_offline_print_me();return false;"': ''
			, esc_html( $attributes['text'] )
			, ('print' == $type) ? sprintf( '<iframe style="display:none;" id="read_offline_print" name="read_offline_print" src="%s"></iframe>',parent::query_url( $post->ID,$post->post_name,$type ) ) : ''
		);

		return $readoffline;
	}


	function shortcode_script_style() {
		wp_enqueue_style( 'read-offline-shortcode', READOFFLINE_URL . '/css/read-offline-shortcode.css',array(), READOFFLINE_VERSION );
	}
}
