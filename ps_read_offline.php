<?php
/*
Plugin Name: Read Offline
Plugin URI: http://soderlind.no/archives/2012/10/01/read-offline/
Description: Download a post or page as pdf, epub, or mobi  (see settings). 
Version: 0.0.2
Author: Per Soderlind
Author URI: http://soderlind.no
*/
/*

Changelog:
v0.0.2: 
* Filename based on the posts slug
* Added meta data
v0.0.1: 
* Initial release

*/
/*
Credits: 
	This template is based on the template at http://pressography.com/plugins/wordpress-plugin-template/ 
	My changes are documented at http://soderlind.no/archives/2010/03/04/wordpress-plugin-template/
*/



if (isset($_GET['ps_read_offline_javascript'])) {
	//embed javascript
	Header("content-type: application/x-javascript");
	echo<<<ENDJS
/**
* @desc Read Offline
* @author Per Soderlind - http://soderlind.no
*/

jQuery(document).ready(function(){
	// add your jquery code here


	//validate plugin option form
  	jQuery("#ps_read_offline_options").validate({
		rules: {
			"ps_read_offline_option_format[]": {
				required: true,
				minlength: 1
			}
		},
		messages: {
			"ps_read_offline_option_format[]": {
				// the ps_read_offline_lang object is define using wp_localize_script() in function ps_read_offline_script() 
				required: ps_read_offline_lang.required,
			}
		},
		errorLabelContainer: jQuery("#ps_read_offline_options div.error")
	});
});

ENDJS;
exit(0);
} 


if (!class_exists('ps_read_offline')) {
	class ps_read_offline {
		/**
		* @var string The options string name for this plugin
		*/
		var $optionsName = 'ps_read_offline_options';

		/**
		* @var array $options Stores the options for this plugin
		*/
		var $options = array();
		/**
		* @var string $localizationDomain Domain used for localization
		*/
		var $localizationDomain = "ps_read_offline";

		/**
		* @var string $url The url to this plugin
		*/ 
		var $url = '';
		/**
		* @var string $urlpath The path to this plugin
		*/
		var $urlpath = '';

		//Class Functions
		/**
		* PHP 4 Compatible Constructor
		*/
		function ps_read_offline(){$this->__construct();}

		/**
		* PHP 5 Constructor
		*/		
		function __construct(){
			//Language Setup
			$locale = get_locale();
			$mo = plugin_dir_path(__FILE__) . 'languages/' . $this->localizationDomain . '-' . $locale . '.mo';	
			load_textdomain($this->localizationDomain, $mo);

			//"Constants" setup
			$this->url = plugins_url(basename(__FILE__), __FILE__);
			$this->urlpath = plugins_url('', __FILE__);	
			//Initialize the options
			$this->getOptions();
			//Admin menu
			add_action("admin_menu", array(&$this,"admin_menu_link"));

			//Actions
			add_action('admin_enqueue_scripts', array(&$this,'ps_read_offline_admin_script')); // or wp_enqueue_scripts, login_enqueue_scripts
			add_action('wp_enqueue_scripts', array(&$this,'ps_read_offline_wp_script')); // or wp_enqueue_scripts, login_enqueue_scripts
		}
		
		function ps_read_offline_admin_script() {
			wp_enqueue_script('jquery'); // other scripts included with Wordpress: http://tinyurl.com/y875age
			wp_enqueue_script('jquery-validate', 'http://ajax.microsoft.com/ajax/jquery.validate/1.6/jquery.validate.min.js', array('jquery')); // other/new versions: http://www.asp.net/ajaxlibrary/cdn.ashx
			wp_enqueue_script('ps_read_offline_script', $this->url.'?ps_read_offline_javascript'); // embed javascript, see end of this file
			wp_localize_script( 'ps_read_offline_script', 'ps_read_offline_lang', array(
				'required' => __('Please select a format below', $this->localizationDomain),
			));
		}
		
		function ps_read_offline_wp_script() {
			wp_enqueue_style('ps_read_offline_style', $this->urlpath.'/e-book-icons/e-book-icons.css','1.0.9'); 
		}
		
		/**
		* @desc Retrieves the plugin options from the database.
		* @return array
		*/
		function getOptions() {
			if (isset($_GET['ps_read_offline_reset']) || !$theOptions = get_option($this->optionsName)) {
				$theOptions = array('ps_read_offline_option_format'=> array('pdf','epub','mobi'),'ps_read_offline_option_zip' => array('no'),'ps_read_offline_option_placement'=>array('widget'));
				update_option($this->optionsName, $theOptions);
			}
			$this->options = $theOptions;
		}
		/**
		* Saves the admin options to the database.
		*/
		function saveAdminOptions(){
			return update_option($this->optionsName, $this->options);
		}

		/**
		* @desc Adds the options subpanel
		*/
		function admin_menu_link() {
			add_options_page('Read Offline', 'Read Offline', 'manage_options', basename(__FILE__), array(&$this,'admin_options_page'));
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
		}

		/**
		* @desc Adds the Settings link to the plugin activate/deactivate page
		*/
		function filter_plugin_actions($links, $file) {
		   $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
		   array_unshift( $links, $settings_link ); // before other links

		   return $links;
		}

		/**
		* Adds settings/options page
		*/
		function admin_options_page() { 
			if(isset($_POST['ps_read_offline_save'])){
				if (! wp_verify_nonce($_POST['_wpnonce'], 'ps_read_offline-update-options') ) {
					die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
				}
				$this->options['ps_read_offline_option_format'] = $_POST['ps_read_offline_option_format'];				   
				$this->options['ps_read_offline_option_zip'] = $_POST['ps_read_offline_option_zip'];
				$this->options['ps_read_offline_option_placement'] = isset($_POST['ps_read_offline_option_placement']) ? $_POST['ps_read_offline_option_placement'] : 'widget';				   

				$this->saveAdminOptions();
				echo '<div class="updated"><p>Success! Your changes were sucessfully saved!</p></div>';
			} ?>			

					   
			<div class="wrap">
			<h2>Read Offline</h2>

			<div style="float:left; width:80%;">
				<form method="post" id="ps_read_offline_options">
				<?php wp_nonce_field('ps_read_offline-update-options'); ?>
					<table width="100%" cellspacing="2" cellpadding="5" class="form-table" > 
						<tr valign="top"> 
							<th width="33%" scope="row">
								<?php _e('Formats available for your visitors', $this->localizationDomain); ?>
								<div class="error" style="display:none;"></div>
							</th> 
							<td>
								<input name="ps_read_offline_option_format[]" id="ps_read_offline_option_format_pdf" type="checkbox" value="pdf" <?php if (in_array('pdf',$this->options['ps_read_offline_option_format'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_format_pdf"><?php _e('PDF', $this->localizationDomain);?></label><br />
								<input name="ps_read_offline_option_format[]" id="ps_read_offline_option_format_epub" type="checkbox" value="epub" <?php if (in_array('epub',$this->options['ps_read_offline_option_format'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_format_epub"><?php _e('ePub', $this->localizationDomain);?></label><br />
								<input name="ps_read_offline_option_format[]" id="ps_read_offline_option_format_mobi" type="checkbox" value="mobi" <?php if (in_array('mobi',$this->options['ps_read_offline_option_format'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_format_mobi"><?php _e('mobi', $this->localizationDomain);?></label><br />
								
								<br /><span class="setting-description"><?php _e("
									If direct linking to ePub and mobi doesn't work, add the following to your .htaccess file:
									<p>
									<div style='font-family:\"Courier New\", Courier, monospace;'>
										AddType application/epub+zip .epub<br/>
										AddType application/x-mobipocket-ebook .mobi
									</div>
									</p>
									Or you can zip the files (select 'yes' below) 
								", $this->localizationDomain); ?>
							</td> 
						</tr>
						<tr valign="top"> 
							<th width="33%" scope="row"><?php _e('Zip the files?', $this->localizationDomain); ?></th> 
							<td>
								
								<input name="ps_read_offline_option_zip[]" disabled="disabled" type="radio" id="ps_read_offline_option_zip_yes" value="yes" <?php if (in_array('yes',$this->options['ps_read_offline_option_zip'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_zip_yes"><?php _e('Yes', $this->localizationDomain);?></label>
								<input name="ps_read_offline_option_zip[]" disabled="disabled" checked="checked" type="radio" id="ps_read_offline_option_zip_no" value="no" <?php if (in_array('no',$this->options['ps_read_offline_option_zip'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_zip_no"><?php _e('No', $this->localizationDomain);?></label><br />
								
								<br /><span class="setting-description"><?php _e("
								<span style='color:red;'>Not implented in this version</span>
								If you choose to zip the files, the files will be moved into one zip archive and only the link to this archive will be presented.
								", $this->localizationDomain); ?>
							</td> 
						</tr>
						<tr valign="top"> 
							<th width="33%" scope="row"><?php _e('Download link placements', $this->localizationDomain); ?></th> 
							<td>
								<input name="ps_read_offline_option_placement[]" disabled="disabled" id="ps_read_offline_option_placement_top_post" type="checkbox" value="top_post" <?php if (in_array('top_post',$this->options['ps_read_offline_option_placement'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_placement_top_post"><?php _e('At the top of the post', $this->localizationDomain);?></label><br />
								<input name="ps_read_offline_option_placement[]" disabled="disabled" id="ps_read_offline_option_placement_bottom_post" type="checkbox" value="bottom_post" <?php if (in_array('bottom_post',$this->options['ps_read_offline_option_placement'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_placement_bottom_post"><?php _e('On the botom of the post', $this->localizationDomain);?></label><br />
								<input name="ps_read_offline_option_placement[]" disabled="disabled" id="ps_read_offline_option_placement_top_page" type="checkbox" value="top_page" <?php if (in_array('top_page',$this->options['ps_read_offline_option_placement'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_placement_top_page"><?php _e('At the top of the page', $this->localizationDomain);?></label><br />
								<input name="ps_read_offline_option_placement[]" disabled="disabled" id="ps_read_offline_option_placement_bottom_page" type="checkbox" value="bottom_page" <?php if (in_array('bottom_page',$this->options['ps_read_offline_option_placement'])) echo ' checked="checked" ';?>/> <label for="ps_read_offline_option_placement_bottom_page"><?php _e('On the botom of the page', $this->localizationDomain);?></label><br />
								<input name="ps_read_offline_option_placement[]" disabled="disabled" type="checkbox" value="widget"  checked="checked"/> <?php _e("In the <a href='widgets.php'>Read Offline widget</a>", $this->localizationDomain);?><br />
								
								<br /><span class="setting-description"><?php _e("<span style='color:red;'>In the 0.0.2, only the widget is implemented</span>", $this->localizationDomain); ?>
							</td> 
						</tr>
					</table>
					<p class="submit"> 
						<input type="submit" name="ps_read_offline_save" class="button-primary" value="<?php _e('Save Changes', $this->localizationDomain); ?>" /> <a href="options-general.php?page=<?php echo basename(__FILE__);?>&ps_read_offline_reset" class="submit"><?php _e('Reset', $this->localizationDomain);?></a>
					</p>
				</form>	
			</div>
			<div style="float:left;width:20%;">
				<div style="float:right;text-align:center;width:200px;padding:5px; border: 2px solid #ccc; background-color: #fff;">
					<p>
						 I'm coding after midnight. You can buy me some  coffees to keep me awake :) Thank you! 
					 </p>
					 <p>
					 	<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><div class="paypal-donations"><input type="hidden" name="cmd" value="_donations"><input type="hidden" name="business" value="HYB5R27TC6W8J"><input type="hidden" name="page_style" value="Donate"><input type="hidden" name="return" value="http://soderlind.no/donate/"><input type="hidden" name="currency_code" value="USD"><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" name="submit" alt="PayPal - The safer, easier way to pay online."><img alt="" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"></div></form>
					 </p>
				</div>
			</div>
			<?php
		}		   
	} //End Class
} //End if class exists statement


if (!class_exists('ps_read_offline_widget')) {
	class ps_read_offline_widget extends WP_Widget {
		function __construct() {	
			parent::__construct(
				'read_offline_widget', // Base ID
				'Read Offline', // Name
				array( 'description' => __( 'Adds a download link for the current post and page. PDF, EPUB and MOBI is supported. Configurable in Settings->Read Offline', 'text_domain' ), ) // Args
			);	
		}
		function widget($args, $instance) {
			global $post;
			
			if (!is_single()) return;
			
			extract($args, EXTR_SKIP);
			echo $before_widget;

			$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
			
			if ( !empty( $title ) ) { 
				echo $before_title . $title . $after_title; 
			}
			echo '<div id="read_offline">';
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
				if (in_array('no', $options['ps_read_offline_option_zip'])) {
					printf(__('Download for offline reading "%s":<br/>'),$post->post_title);
					echo "<ul>";
					foreach ($formats as $type => $document_type) {
						printf ('<li><a class="%s" href="%s?id=%s&read-offline=%s" title="%s %s.%s">%s%s</a></li>',
							$type,plugins_url("download.php", __FILE__),$post->ID,$type,
							__('Download '),$post->post_name,$type,
							__('Download '),$document_type
						);
					}
					echo "</ul>";
				} else {			
					$values = array_values($formats);				
					$str_formats = (count($values) > 2) 
						? implode(', ', array_slice($values, 0, count($values)-1)) . __(' and ') . implode(array_slice($values, -1)) 
						: implode(__(' and '), $values);
					
					printf(__('<p><a href="%s">Download a .zip</a> file including "%s" in %s %s.</p>'),$post->ID, $post->post_title, $str_formats,(count($values)>1) ? 'formats' : 'format');
				}			
			echo '</div>';

			echo $after_widget;
		}
		function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			return $instance;
		}
		function form($instance) {
			$instance = wp_parse_args( (array) $instance, array( 'title' => ''  ) );
			$title = strip_tags($instance['title']);
	?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
	<?php
		}
	} // end class
	add_action( 'widgets_init', create_function( '', 'register_widget( "ps_read_offline_widget" );' ) );
}


if (class_exists('ps_read_offline')) { 
	$ps_read_offline_var = new ps_read_offline();
}
?>