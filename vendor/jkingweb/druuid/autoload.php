<?php
if(!@class_exists("JKingWeb\\DrUUID\\UUID")) { // checks for pre-existence of a compatible autoloader 
	spl_autoload_register(function ($class) {
		if(strpos($class,"JKingWeb\\DrUUID\\")!==0) return false; 
		$base = __DIR__.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR;
		$name = substr($class, 16);
		$file = $base.str_replace("\\", DIRECTORY_SEPARATOR, $name).".php";
		if (file_exists($file)) {
			require_once $file;
		}
	});
}