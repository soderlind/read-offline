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

		// if options shorcode NN enabled
		add_shortcode( 'pdf', array($this,'pdf'));
		add_shortcode( 'epub', array($this,'epub'));
		add_shortcode( 'mobi', array($this,'mobi'));
		//add_shortcode( 'print', array($this,'print'));

		add_action('admin_enqueue_scripts', array($this,'shortcode_script_style'));


		if (is_admin()) {
			if (! class_exists('Shortcode_UI')) {
				require_once (READOFFLINE_PATH . '/inc/class-shortcode-ui.php');
			}
			if (! class_exists('Shortcode_UI_Fields')) {
				require_once (READOFFLINE_PATH . '/inc/fields/class-shortcode-ui-fields.php');
			}
			if (! class_exists('Shortcake_Field_Attachment')) {
				require_once (READOFFLINE_PATH  . '/inc/fields/class-field-attachment.php');
			}

			add_action( 'init', function() {
				$shortcode_ui     = Shortcode_UI::get_instance();
				$fields           = Shortcode_UI_Fields::get_instance();
				$attachment_field = Shortcake_Field_Attachment::get_instance();
			},5);

			Shortcode_UI::get_instance()->register_shortcode_ui(
				'epub',
				array(
					'label' => "ePub",
					// Icon/image for shortcode. Optional. src or dashicons-$icon. Defaults to carrot.
					'listItemImage' => 'dashicons-book', // https://developer.wordpress.org/resource/dashicons/
					// Available shortcode attributes and default values. Required. Array.
					// Attribute model expects 'attr', 'type' and 'label'
					// Supported field types: text, checkbox, textarea, radio, select, email, url, number, and date.
					'attrs' => array(
						array(
							'label'   => __('Placement','read-offline'),
							'attr'    => 'position',
							'type'    => 'select',
							'options' => array( // List of options  value => label
								'none'     => __('None','read-offline'),
								'left'  => __('Left','read-offline'),
								'center' => __('Center','read-offline'),
								'right'   => __('Right','read-offline'),
		                    ),
		                    'value' => 'none', // selected option value
		                    'description' => __('Select "None" if you want to use your own CSS to control the placement.','read-offline'),
		                ),
		                array(
							'label' => __('Cover Art','read-offline'),
							'attr'  => 'cover',
							'type'  => 'select',
							'options' => array( // List of options  value => label
		                        'none'  => __('None','read-offline'),
		                        'default'  => __('Default','read-offline'),
		                        'featured'  => __('Featured Image','read-offline'),
		                        'custom'  => __('Image (added below)','read-offline'),
		                    ),
		                    'value' => 'default', // selected option value
		                    'description' => __('"Default" is the cover art set in Read Offline -> ePub','read-offline'),
		                ),
		                array(
							'label' => __('Image','read-offline'),
							'attr'  => 'image',
							'type'  => 'attachment',
							'libraryType' => array( 'image' ),
							'addButton'   => __('Select Cover','read-offline'),
							'frameTitle'  => __('Select Cover','read-offline'),
						),
						array(
							'label' => __('ePub Style','read-offline'),
							'attr'  => 'style',
							'type'  => 'select',
							'options' => array( // List of options  value => label
		                        'none'  => __('None','read-offline'),
		                        'default'  => __('Custom','read-offline'),
		                    ),
		                    'value' => 'default', // selected option value
		                    'description' => __('"Custom" is the custom style set in Read Offline -> ePub','read-offline'),
		                )
/*
						array(
							'label' => __('Text','l10n-domain'),
							'attr'  => 'mytext',
							'type'  => 'text',
		                    'placeholder' => __('Text field','l10n-domain'),
		                    'description' => 'This is a text field',
		                ),
						array(
							'label' => __('Textarea','l10n-domain'),
							'attr'  => 'mytextarea',
							'type'  => 'textarea',
		                    'placeholder' => __('Textarea field','l10n-domain'),
		                    'description' => __('This is a textarea field','l10n-domain'),
		                ),
						array(
							'label' => __('Email','l10n-domain'),
							'attr'  => 'myemail',
							'type'  => 'email',
		                    'placeholder' => __('email field','l10n-domain'),
		                    'description' => __('This is a email field','l10n-domain'),
		                ),
						array(
							'label' => __('URL','l10n-domain'),
							'attr'  => 'myurl',
							'type'  => 'url',
		                    'placeholder' => __('URL field','l10n-domain'),
		                    'description' => __('This is a URL field','l10n-domain'),
		                ),
						array(
							'label' => __('Date','l10n-domain'),
							'attr'  => 'mydate',
							'type'  => 'date',
		                    'placeholder' => __('dd.mm.yy','l10n-domain'),
		                    'description' => __('This is a date field','l10n-domain'),
		                ),
		                array(
							'label' => __('Checkbox','l10n-domain'),
							'attr'  => 'mycheckbox',
							'type'  => 'checkbox',
		                    'value' => 'true', // true = checked
		                    'description' => __('This is a checkbox field','l10n-domain'),
		                ),
						array(
							'label' => __('Radio','l10n-domain'),
							'attr'  => 'myradio',
							'type'  => 'radio',
							'options' => array( // List of options  value => label
		                        'a'  => __('Alfa','l10n-domain'),
		                        'b'  => __('Beta','l10n-domain'),
		                    ),
		                    'value' => 'a', // checked radio value
		                    'description' => __('This is a radio field','l10n-domain'),
		                ),
						array(
							'label' => __('Select','l10n-domain'),
							'attr'  => 'myselect',
							'type'  => 'select',
							'options' => array( // List of options  value => label
		                        '1'  => __('One','l10n-domain'),
		                        '2'  => __('Two','l10n-domain'),
		                    ),
		                    'value' => '2', // selected option value
		                    'description' => __('This is a select field','l10n-domain'),
		                ),
		                array(
							'label' => 'Attachment',
							'attr'  => 'coverpage',
							'type'  => 'attachment',
							'libraryType' => array( 'image' ),
							'addButton'   => 'Select Image',
							'frameTitle'  => 'Select Image',
						),
*/
					),
				)
			);
			Shortcode_UI::get_instance()->register_shortcode_ui(
				'pdf',
				array(
					'label' => "PDF",
					// Icon/image for shortcode. Optional. src or dashicons-$icon. Defaults to carrot.
					'listItemImage' => 'dashicons-book', // https://developer.wordpress.org/resource/dashicons/
					// Available shortcode attributes and default values. Required. Array.
					// Attribute model expects 'attr', 'type' and 'label'
					// Supported field types: text, checkbox, textarea, radio, select, email, url, number, and date.
					'attrs' => array(
						array(
							'label' => __('Cover Art','read-offline'),
							'attr'  => 'cover',
							'type'  => 'select',
							'options' => array( // List of options  value => label
		                        'none'  => __('None','read-offline'),
		                        'default'  => __('Default','read-offline'),
		                        'featured'  => __('Featured Image','read-offline'),
		                        'custom'  => __('Image (added below)','read-offline'),
		                    ),
		                    'value' => 'default', // selected option value
		                    'description' => __('"Default" is the cover art set in Read Offline -> ePub','read-offline'),
		                ),
		                array(
							'label' => __('Image','read-offline'),
							'attr'  => 'image',
							'type'  => 'attachment',
							'libraryType' => array( 'image' ),
							'addButton'   => __('Select Cover','read-offline'),
							'frameTitle'  => __('Select Cover','read-offline'),
						),
						array(
							'label' => __('ePub Style','read-offline'),
							'attr'  => 'style',
							'type'  => 'select',
							'options' => array( // List of options  value => label
		                        'none'  => __('None','read-offline'),
		                        'default'  => __('Custom','read-offline'),
		                    ),
		                    'value' => 'default', // selected option value
		                    'description' => __('"Custom" is the custom style set in Read Offline -> ePub','read-offline'),
		                )
					),
				)
			);

			Shortcode_UI::get_instance()->register_shortcode_ui(
				'mobi',
				array(
					'label' => "mobi",
					// Icon/image for shortcode. Optional. src or dashicons-$icon. Defaults to carrot.
					'listItemImage' => 'dashicons-book', // https://developer.wordpress.org/resource/dashicons/
					// Available shortcode attributes and default values. Required. Array.
					// Attribute model expects 'attr', 'type' and 'label'
					// Supported field types: text, checkbox, textarea, radio, select, email, url, number, and date.
					'attrs' => array(
						array(
							'label' => __('Cover Art','read-offline'),
							'attr'  => 'cover',
							'type'  => 'select',
							'options' => array( // List of options  value => label
		                        'none'  => __('None','read-offline'),
		                        'default'  => __('Default','read-offline'),
		                        'featured'  => __('Featured Image','read-offline'),
		                        'custom'  => __('Image (added below)','read-offline'),
		                    ),
		                    'value' => 'default', // selected option value
		                    'description' => __('"Default" is the cover art set in Read Offline -> ePub','read-offline'),
		                ),
		                array(
							'label' => __('Image','read-offline'),
							'attr'  => 'image',
							'type'  => 'attachment',
							'libraryType' => array( 'image' ),
							'addButton'   => __('Select Cover','read-offline'),
							'frameTitle'  => __('Select Cover Art','read-offline'),
						),
					),
				)
			);

		}
	}

	function pdf ($attributes, $content) {
		// $attributes = shortcode_atts(array( // default values
		// 	'nn'   	=>	'value' //
		// 	),
		// 	$attributes
		// );
		//sleep(3600);
		// exit();
		if (is_admin()) {
		ob_start();
		?>
		<!--section class="pullquote" style"width:100%;"-->
			<div class='plugin_icon' style='height:30px;width:100px;margin:0 auto;diplay:block;/*position: relative; top: 50%;transform: translateY(-50%);*/'>
					<span class='dashicons dashicons-book' style='font-size:18px;vertical-align: middle;'></span> <span style='font-size:18px;'>PDF</span>
			</div>
		<!--/section-->
		<?php
		return ob_get_clean();
		} else {
			return "<h1>hei</h1>";
		} 
	}

	function epub ($attributes, $content) {
		// $attributes = shortcode_atts(array( // default values
		// 	'nn'   	=>	'value' //
		// 	),
		// 	$attributes
		// );
		//sleep(3600);
		// exit();
		if (is_admin()) {
		ob_start();
		?>
		<!--section class="pullquote" style"width:100%;"-->
			<div class='plugin_icon' style='height:30px;width:100px;margin:0 auto;diplay:block;/*position: relative; top: 50%;transform: translateY(-50%);*/'>
					<span class='dashicons dashicons-book' style='font-size:18px;vertical-align: middle;'></span> <span style='font-size:18px;'>ePub</span>
			</div>
		<!--/section-->
		<?php
		return ob_get_clean();
		} else {
			return "<h1>hei</h1>";
		} 
	}

	function mobi ($attributes, $content) {
		// $attributes = shortcode_atts(array( // default values
		// 	'nn'   	=>	'value' //
		// 	),
		// 	$attributes
		// );
		//sleep(3600);
		// exit();
		if (is_admin()) {
		ob_start();
		?>
		<!--section class="pullquote" style"width:100%;"-->
			<div class='plugin_icon' style='height:30px;width:100px;margin:0 auto;diplay:block;/*position: relative; top: 50%;transform: translateY(-50%);*/'>
					<span class='dashicons dashicons-book' style='font-size:18px;vertical-align: middle;'></span> <span style='font-size:18px;'>mobi</span>
			</div>
		<!--/section-->
		<?php
		return ob_get_clean();
		} else {
			return "<h1>hei</h1>";
		} 
	}




	function shortcode_script_style() {
		wp_enqueue_style('read-offline-embed', READOFFLINE_URL . '/css/read-offline-admin.css',array(), READOFFLINE_VERSION );
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