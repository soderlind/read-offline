<?php
error_reporting(E_ALL | E_STRICT);
ini_set('error_reporting', E_ALL | E_STRICT);
ini_set('display_errors', 1);

include "../vendor/autoload.php";

use grandt\ResizeGif\Debug\DebugGif;
use grandt\ResizeGif\Debug\HexBlock;
use grandt\ResizeGif\Files\FileHandler;

// Rotating_earth and SmallFullColourGIF are from the GIF Wikipedia page.
// $srcFile = 'resources/Rotating_earth_(large).gif';
// $srcFile = 'resources/SmallFullColourGIF.gif';
$srcFile = 'resources/frame_diff_ir2t.gif';
// $srcFile = 'test.gif';


echo "<!doctype html><html><head><title>ResizeGif.Test2</title></head><body><pre>\n";


echo "Dumping gif stats for $srcFile:\n";
DebugGif::dumpGif($srcFile);
// DebugGif::dumpGif($srcFile, "."); // Dump the frames in the current directory.
$fh = new FileHandler();
$fh->openFile($srcFile);
echo "\n\n" . HexBlock::printBlock($fh->getHandle(), 64, false) . "\n";
$fh->closeFile();
echo "</pre></body></html>\n";
