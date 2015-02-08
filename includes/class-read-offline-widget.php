<?php

class Read_Offline_Widget extends WP_Widget {

	var $localizationDomain = "read-offline";

	private static $instance;

	public static function get_instance() {

		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new self();
		return self::$instance;
	}

	function __construct() {
		parent::__construct(
			'read_offline_widget', // Base ID
			'Read Offline', // Name
			array( 'description' => __( 'Adds a download link for the current post and page. Configurable in Settings->Read Offline', $this->localizationDomain ), ) // Args
		);
	}
	function widget($args, $instance) {
		global $post;

		if (!is_single() && !is_page()) return;

		extract($args, EXTR_SKIP);
		echo $before_widget;


		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
		$text = empty($instance['text']) ? ' ' : apply_filters('widget_text', $instance['text']);

		$icononly = ($instance['icononly'] == 'yes');

		$text = str_ireplace('%title%', $post->post_title,$text);
		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
		echo '<div class="readoffline-widget">';
			$options = get_option('ps_read_offline_options');
			$formats = array_uintersect(
				array(
					'pdf' => 'PDF'
					,'epub' => 'ePub'
					,'mobi' => 'mobi'
				)
				, $options['ps_read_offline_option_format']
				, "strcasecmp"
			);
			echo  $text, '<br/>';

			if ($icononly === true) {
				foreach ($formats as $type => $document_type) {
					printf ('<div><a class="%s" href="%s" title="%s %s.%s"></a></div>',
						$type,$this->ps_read_offline_url($post->ID,$post->post_name,$type),
						__('Download ',$this->localizationDomain),$post->post_name,$type
					);
				}
			} else {
				echo "<ul>";
				foreach ($formats as $type => $document_type) {
					printf ('<li><a class="%s" href="%s" title="%s %s.%s">%s%s</a></li>',
						$type,$this->ps_read_offline_url($post->ID,$post->post_name,$type),
						__('Download ',$this->localizationDomain),$post->post_name,$type,
						__('Download ',$this->localizationDomain),$document_type
					);
				}
				echo "</ul>";
			}
		echo '</div>';

		echo $after_widget;
	}


	function ps_read_offline_url($id,$name,$format) {
		$rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
		if ( isset($rules)) {
			return sprintf("%s/read-offline/%s/%s.%s",home_url(),$id,$name,$format);
		} else {
			return sprintf("%s/index.php?read_offline_id=%s&read_offline_name=%s&&read_offline_format=%s",home_url(),$id,$name,$format);
		}
	}

	function ps_read_offline_ga_tacking($dotracking,$type,$name) {
		if (in_array('yes',$dotracking)) {
			return sprintf("onclick=\"if (typeof _gaq !== 'undefined' && _gaq !== null) {_gaq.push(['_trackEvent', 'read-offline','%s','%s']);}return true;\"",$type,$name);
		} else {
			return "";
		}
	}


	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['text'] = strip_tags($new_instance['text']);
		$instance['icononly'] = $new_instance['icononly'];

		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '', 'icononly' => 'no'  ));
		$title = strip_tags($instance['title']);
		$text = strip_tags($instance['text']);
		$icononly = $instance['icononly'];
		printf('<p><label for="%s">%s <input class="widefat" id="%s" name="%s" type="text" value="%s" /></label></p>',$this->get_field_id('title'),__('Title:',$this->localizationDomain),$this->get_field_id('title'),$this->get_field_name('title'),esc_attr($title));
		printf('<p><label for="%s">%s <textarea class="widefat" id="%s" name="%s" >%s</textarea></label></p>',$this->get_field_id('text'),__('Text:',$this->localizationDomain),$this->get_field_id('text'),$this->get_field_name('text'),esc_attr($text));
		printf('
			<p>Icons only?:
				<input class="radio" type="radio" %s id="%s_yes" name="%s" value="yes"/> <label for="%s_yes">%s</label>
				<input class="radio" type="radio" %s id="%s_no" name="%s" value="no"/> <label for="%s_no">%s</label>
			</p>',
				checked( $instance['icononly'], 'yes', false),$this->get_field_id( 'icononly' ),$this->get_field_name( 'icononly' ),$this->get_field_id( 'icononly' ),__('Yes'),
				checked( $instance['icononly'], 'no', false), $this->get_field_id( 'icononly' ),$this->get_field_name( 'icononly' ),$this->get_field_id( 'icononly' ),__('No')
		);
	}
} // end class