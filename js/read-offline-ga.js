/**
* @desc Read Offline Google Analytics
* @author Per Soderlind - http://soderlind.no
*/


jQuery(document).ready(function(){
	// try log read-offline event to google analytics      	
	if (typeof _gaq !== "undefined" && _gaq !== null) { // make sure you are running async google analytics: http://code.google.com/apis/analytics/docs/tracking/asyncTracking.html
		jQuery("a[href$='.print'],a[href$='.epub'],a[href$='.mobi'],a[href$='.pdf']").on("click", function(event){
			var ps_readoffline_filename = jQuery(this).attr("href").split('/').pop();
			var ps_readoffline_filetype = jQuery(this).attr("href").split('.').pop();
			_gaq.push(['_trackEvent', 'read-offline', ps_readoffline_filetype, ps_readoffline_filename]);
		});
	}
});