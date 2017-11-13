<?php 
/**
	Admin Page Framework v3.8.15 by Michael Uno 
	Generated by PHP Class Files Script Generator <https://github.com/michaeluno/PHP-Class-Files-Script-Generator>
	<http://en.michaeluno.jp/read-offline>
	Copyright (c) 2013-2017, Michael Uno; Licensed under MIT <http://opensource.org/licenses/MIT> */
class Read_Offline_Settings_AdminPageFramework_Form_View___Script_Form extends Read_Offline_Settings_AdminPageFramework_Form_View___Script_Base {
    static public function getScript() {
        return <<<JAVASCRIPTS
( function( $ ) {

    var _removeRead_Offline_Settings_AdminPageFrameworkLoadingOutputs = function() {

        jQuery( '.read-offline-form-loading' ).remove();
        jQuery( '.read-offline-form-js-on' )
            .hide()
            .css( 'visibility', 'visible' )
            .fadeIn( 200 )
            .removeClass( '.read-offline-form-js-on' );
    
    }

    /**
     * When some plugins or themes have JavaScript errors and the script execution gets stopped,
     * remove the style that shows "Loading...".
     */
    var _oneerror = window.onerror;
    window.onerror = function(){
        
        // We need to show the form.
        _removeRead_Offline_Settings_AdminPageFrameworkLoadingOutputs();
        
        // Restore the original
        window.onerror = _oneerror;
        
        // If the original object is a function, execute it;
        // otherwise, discontinue the script execution and show the error message in the console.
        return "function" === typeof _oneerror
            ? _oneerror()      
            : false; 
       
    }
    
    /**
     * Rendering forms is heavy and unformatted layouts will be hidden with a script embedded in the head tag.
     * Now when the document is ready, restore that visibility state so that the form will appear.
     */
    jQuery( document ).ready( function() {
        _removeRead_Offline_Settings_AdminPageFrameworkLoadingOutputs();
    });    

    /**
     * Gets triggered when a widget of the framework is saved.
     * @since    3.7.0
     */
    $( document ).bind( 'read-offline_saved_widget', function( event, oWidget ){
        jQuery( '.read-offline-form-loading' ).remove();
    });    
    
}( jQuery ));
JAVASCRIPTS;
        
    }
    static private $_bLoadedTabEnablerScript = false;
}
