<?php
/* 
    Plugin Name: Read Offline 2
    Plugin URI: http://soderlind.no/archives/2012/10/01/read-offline/
    Description: lorem ipsum dolores est
    Author: Per Soderlind
    Author URI: http://soderlind.no
*/

 include_once(dirname( __FILE__ ) ."/settings/read-offline-settings.php");

 if ( is_admin() )
 	new Read_Offline_Settings ();