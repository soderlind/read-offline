<?php
error_reporting(E_ALL | E_STRICT);
ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);

include "../vendor/autoload.php";

use grandt\ResizeGif\ExtractGif;

// Rotating_earth and SmallFullColourGIF are from the GIF Wikipedia page.
// $srcFile = 'resources/Rotating_earth_(large).gif';
$srcFile = 'resources/SmallFullColourGIF.gif';
//$srcFile = 'resources/frame_diff_ir2t.gif';
// $srcFile = 'test.gif';

ExtractGif::extract($srcFile, "."); // Dump the frames in the current directory.
