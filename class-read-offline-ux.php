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
		add_action('the_content', array($this,'embed'));
		add_action('wp_enqueue_scripts', array($this,'add_script_style'));
	}

	function add_script_style() {
		wp_enqueue_style('read-offline-embed', parent::$plugin_url . 'library/css/read-offline-embed.css',array(),parent::$plugin_version);
		wp_enqueue_style('read-offline-css', parent::$plugin_url   . 'library/css/read-offline.css',array(),parent::$plugin_version);
		wp_enqueue_script('read-offline',      parent::$plugin_url . 'library/js/read-offline-wp.js',array('jquery'),parent::$plugin_version);

		// google analytics track event
		if ( '1' == parent::$options['misc']['google'] ) {
			wp_enqueue_script('read-offline-ga', parent::$plugin_url.'library/js/read-offline-ga.js',array('jquery'),parent::$plugin_version);
		}

	}

	function embed($content) {
		global $post;
		if (!is_object($post)) return;

		$placements = array_intersect_assoc(
			array(
				 'top'    => 1
				,'bottom' => 1
			)
			, parent::$options['where']['placements']
		);
		//printf("<pre>%s</pre>",print_r($placements,true));
		$formats = array_intersect_key(
				array(
					 'pdf'   => __('Download PDF',  'read-offline')
					,'epub'  => __('Download ePub', 'read-offline')
					,'mobi'  => __('Download mobi', 'read-offline')
					,'docx'  => __('Download DocX', 'read-offline')
					,'print' => __('Print',         'read-offline')
				)
				, array_filter(parent::$options['what']['formats'])
		);
		//printf("<pre>%s</pre>",print_r($formats,true));

		$readoffline ='<div class="readoffline-embed">';

		if ('' != parent::$options['how']['link_text']) {
			$text = parent::$options['how']['link_text'];
			$text = str_ireplace('%title%', $post->post_title,$text);
			$readoffline .= sprintf('<div class="readoffline-embed-text">%s</div>',stripslashes($text));
		}
		foreach ($formats as $type => $document_type) {
			$str_info =  (1 == parent::$options['how']['icons_only']) ? '' : sprintf("%s",$document_type);
			$readoffline .= sprintf ('<div><a class="%s" href="%s" title="%s %s.%s" %s>%s</a></div>%s',
				  $type
				, parent::query_url($post->ID,$post->post_name,$type)
				, ('print' == $type) ? __('Print','read-offline') : __('Download','read-offline')
				, $post->post_name
				, ('print' == $type) ? '' : $type
				, ('print' == $type) ? 'onclick="read_offline_print_me();return false;"': ''
				, $str_info
				, ('print' == $type) ? sprintf('<iframe style="display:none;" id="read_offline_print" name="read_offline_print" src="%s"></iframe>',parent::query_url($post->ID,$post->post_name,$type)) : ''
			);
		}
		$readoffline .= "</div>";

		// POST TYPE CHECK

		if ( is_single() && isset($placements['top']) ) {
			$content = $readoffline . $content;
		}
		if ( is_single() && isset($placements['bottom']) ) {
			$content =  $content . $readoffline;
		}


		return /*sprintf("<pre>%s</pre>",print_r(parent::$options,true)) .*/ $content;
	}

}