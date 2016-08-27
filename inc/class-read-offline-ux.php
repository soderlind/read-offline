<?php
class Read_Offline_UX extends Read_Offline {


	private static $instance;
	//private $url;

	public static function get_instance() {

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {

		parent::get_instance();

		//$this->url = plugin_dir_url( dirname(__FILE__) );
		add_action( 'the_content', array( $this, 'embed_post' ) );
		//add_action('the_category', array($this,'embed_category'));
		add_filter( 'get_the_archive_title', array( $this, 'embed_category' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_script_style' ) );
	}

	function add_script_style() {
		wp_enqueue_style( 'read-offline-embed', READOFFLINE_URL . '/css/read-offline-embed.css',array(), READOFFLINE_VERSION );
		wp_enqueue_style( 'read-offline-css',   READOFFLINE_URL . '/css/read-offline.css',array(), READOFFLINE_VERSION );
		wp_enqueue_script( 'read-offline',      READOFFLINE_URL . '/js/read-offline-wp.js',array( 'jquery' ), READOFFLINE_VERSION );

		// google analytics track event
		if ( '1' == parent::$options['misc']['google'] ) {
			wp_enqueue_script( 'read-offline-ga', READOFFLINE_URL.'/js/read-offline-ga.js',array( 'jquery' ), READOFFLINE_VERSION );
		}

	}

	function embed_category( $title ) {
		return $title;
	}

	function embed_post( $content ) {
		global $post;
		if ( ! is_object( $post ) ) { return; }

		$placements = array_intersect_assoc(
			array(
				 'top'    => 1,
			'bottom' => 1,
			)
			, parent::$options['where']['placements']
		);
		//printf("<pre>%s</pre>",print_r($placements,true));
		$formats = array_intersect_key(
			array(
					 'pdf'   => __( 'Download PDF',  'read-offline' ),
			'epub'  => __( 'Download ePub', 'read-offline' ),
			'mobi'  => __( 'Download mobi', 'read-offline' ),
			'docx'  => __( 'Download DocX', 'read-offline' ),
			'print' => __( 'Print',         'read-offline' ),
				)
				, array_filter( parent::$options['what']['formats'] )
		);
		//printf("<pre>%s</pre>",print_r($formats,true));

		$readoffline = '<div class="readoffline-embed">';

		if ( '' != parent::$options['how']['link_text'] ) {
			$text = parent::$options['how']['link_text'];
			$text = str_ireplace( '%title%', $post->post_title,$text );
			$readoffline .= sprintf( '<div class="readoffline-embed-text">%s</div>',stripslashes( $text ) );
		}
		foreach ( $formats as $type => $document_type ) {
			$str_info = (1 == parent::$options['how']['icons_only']) ? '' : sprintf( '%s',$document_type );
			$readoffline .= sprintf('<div><a class="%s" href="%s" title="%s %s.%s" %s>%s</a></div>%s',
				$type
				, parent::query_url( $post->ID,$post->post_name,$type )
				, ('print' == $type) ? __( 'Print','read-offline' ) : __( 'Download','read-offline' )
				, $post->post_name
				, ('print' == $type) ? '' : $type
				, ('print' == $type) ? 'onclick="read_offline_print_me();return false;"': ''
				, $str_info
				, ('print' == $type) ? sprintf( '<iframe style="display:none;" id="read_offline_print" name="read_offline_print" src="%s"></iframe>',parent::query_url( $post->ID,$post->post_name,$type ) ) : ''
			);
		}
		$readoffline .= '</div>';

		// POST TYPE CHECK

		$post_types = array_keys(array_intersect(
			parent::$options['where']['post_types'],
			array(
				 'post' => 1,
				 'page' => 1,
			)
		));

		if ( is_singular( $post_types ) && isset( $placements['top'] ) ) {
			$content = $readoffline . $content;
		}
		if ( is_singular( $post_types ) && isset( $placements['bottom'] ) ) {
			$content = $content . $readoffline;
		}

		return /*sprintf("<pre>%s</pre>",print_r(parent::$options,true)) .*/ $content;
	}
}
