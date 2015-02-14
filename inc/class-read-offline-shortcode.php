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
		//add_shortcode( 'pdf', array($this,'pdf'));
		add_shortcode( 'epub', array($this,'epub'));
		//add_shortcode( 'mobi', array($this,'mobi'));
		//add_shortcode( 'print', array($this,'print'));

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
					'label' => "Read Offline ePub",
					// Icon/image for shortcode. Optional. src or dashicons-$icon. Defaults to carrot.
					'listItemImage' => 'dashicons-media-text', // https://developer.wordpress.org/resource/dashicons/
					// Available shortcode attributes and default values. Required. Array.
					// Attribute model expects 'attr', 'type' and 'label'
					// Supported field types: text, checkbox, textarea, radio, select, email, url, number, and date.
					'attrs' => array(
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
					),
				)
			);
		}
	}


	function epub ($attributes, $content) {
		// $attributes = shortcode_atts(array( // default values
		// 	'nn'   	=>	'value' //
		// 	),
		// 	$attributes
		// );


		ob_start();
		?>

		<p class="epub" style="style="text-align: right;" background: rgba(0,0,0,0.1);">
			
			<span class="dashicons dashicons-media-document"></span>ePub
			<p><?php printf("<pre>%s</pre>",print_r($attributes,true));?></p>
		</p>

		<?php
		return ob_get_clean();
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