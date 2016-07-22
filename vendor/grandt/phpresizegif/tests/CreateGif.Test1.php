<?php
error_reporting(E_ALL | E_STRICT);
ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);

include "../vendor/autoload.php";

use grandt\ResizeGif\CreateGif;

$gif = new CreateGif(100, 100, array('r' => 0, 'g' => 127, 'b' => 127) );
$gif->addNetscapeApplicationBlock();
$gif->addCommentBlock("This is a test. The quick brown fox jumped over the lazy dog");
$gif->addCommentBlock("This is a long test. The quick brown fox jumped over the lazy dog. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec magna lorem, mattis sit amet porta vitae, consectetur ut eros. Nullam id mattis lacus. In eget neque magna, congue imperdiet nulla. Aenean erat lacus, imperdiet a adipiscing non, dignissim eget felis. Nulla facilisi. Vivamus sit amet lorem eget mauris dictum pharetra. In mauris nulla, placerat a accumsan ac, mollis sit amet ligula. Donec eget facilisis dui. Cras elit quam, imperdiet at malesuada vitae, luctus id orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Pellentesque eu libero in leo ultrices tristique. Etiam quis ornare massa. Donec in velit leo. Sed eu ante tortor.");

$tFile = createImageSquare();
$gif->addImage($tFile);
unlink($tFile);

$tFile = createImageEllipse(255, 0, 0);
$gif->addFrame($tFile, 500, 0, 0, CreateGif::DISPOSAL_RESTORE_TO_PREVIOUS);
unlink($tFile);

$tFile = createImageEllipse(0, 255, 0);
$gif->addFrame($tFile, 500, 50, 0, CreateGif::DISPOSAL_RESTORE_TO_PREVIOUS);
unlink($tFile);

$tFile = createImageEllipse(0, 0, 255);
$gif->addFrame($tFile, 500, 50, 50, CreateGif::DISPOSAL_RESTORE_TO_PREVIOUS);
unlink($tFile);

$tFile = createImageEllipse(255, 255, 255);
$gif->addFrame($tFile, 500, 0, 50, CreateGif::DISPOSAL_RESTORE_TO_PREVIOUS);
unlink($tFile);

$gif->closeGif();

/**
 * @param int $r
 * @param int $g
 * @param int $b
 *
 * @return string
 */
function createImageEllipse($r, $g, $b) {
    $image_p = imagecreatetruecolor(50, 50);
    imagetruecolortopalette($image_p, false, 256);
    $transparent = imagecolorallocatealpha($image_p, 255, 0, 255, 127);
    imagefill($image_p, 0, 0, $transparent);
    imagecolortransparent($image_p, $transparent);

// choose a color for the ellipse
    $col_ellipse = imagecolorallocate($image_p, $r, $g, $b);
    imagefilledellipse($image_p, 25, 25, 20, 20, $col_ellipse);

    imagealphablending($image_p, true);
    imagesavealpha($image_p, true);

    $tFile = tempnam("BewareOfGeeksBearingGifs", "grD");
    imagegif($image_p, $tFile);

    return $tFile;
}

/**
 * @return string
 */
function createImageSquare() {
    $image_p = imagecreatetruecolor(100, 100);
    imagetruecolortopalette($image_p, false, 256);
    $transparent = imagecolorallocatealpha($image_p, 255, 0, 255, 127);
    imagefill($image_p, 0, 0, $transparent);
    imagecolortransparent($image_p, $transparent);

    $color = imagecolorallocate($image_p, 0, 80, 80);
    imagefill($image_p, 0, 0, $color);

    imagealphablending($image_p, true);
    imagesavealpha($image_p, true);

    $tFile = tempnam("BewareOfGeeksBearingGifs", "grD");
    imagegif($image_p, $tFile);

    return $tFile;
}
