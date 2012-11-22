/**
* @desc Read Offline Admin
* @author Per Soderlind - http://soderlind.no
*/

jQuery(document).ready(function(){
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
				required: oJs_ps_read_offline.str_required,
			}
		},
		errorLabelContainer: jQuery("#ps_read_offline_options div.error")
	});
});