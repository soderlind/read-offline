<?php
error_reporting(E_ALL | E_STRICT);
ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);

include "../vendor/autoload.php";

use grandt\ResizeGif\ResizeGif;

// Rotating_earth and SmallFullColourGIF are from the GIF Wikipedia page.
//// $srcFile = 'resources/Rotating_earth_(large).gif';
$srcFile = 'resources/SmallFullColourGIF.gif';
// $srcFile = 'resources/frame_diff_ir2t.gif';
$dstFile1 = 'resources/test1.gif';
$dstFile2 = 'resources/test2.gif';
$dstFile3 = 'resources/test3.gif';
echo "<!doctype html><html><head><title>ResizeGif.Test1</title></head><body>
<p>Files are used for in and output, as you really don't want to
dynamically resize animated gifs every time they are used.</p><pre>\n";

showStats($srcFile);

echo "Resizing to half size\n";
ResizeGif::ResizeByRatio($srcFile, $dstFile1, 0.5);
showStats($dstFile1);

echo "Resizing to 2x\n";
ResizeGif::ResizeByRatio($srcFile, $dstFile2, 2);
showStats($dstFile2);

echo "Resizing to 200px wide\n";
ResizeGif::ResizeToWidth($srcFile, $dstFile3, 200);
showStats($dstFile3);

echo "</pre></body></html>\n";

/**
 * @param string $file
 * @throws Exception
 */
function showStats($file) {
    $size = ResizeGif::getSize($file);
    echo "File: $file:\n";
    echo " - width.: " . $size['width'] . "\n";
    echo " - height: " . $size['height'] . "\n";
    echo "<img src='$file' />\n";
    echo "\n";
}
