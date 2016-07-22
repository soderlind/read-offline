<?php
/**
 * Copyright (C) 2015  A. Grandt
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @author    A. Grandt <php@grandt.com>
 * @copyright 2015- A. Grandt
 * @license   GNU LGPL 2.1
 */
namespace grandt\ResizeGif;

use com\grandt\BinStringStatic;
use Exception;
use grandt\ResizeGif\Files\FileHandler;
use grandt\ResizeGif\Structure\AbstractExtensionBlock;
use grandt\ResizeGif\Structure\ApplicationExtension;
use grandt\ResizeGif\Structure\CommentExtension;
use grandt\ResizeGif\Structure\GraphicControlExtension;
use grandt\ResizeGif\Structure\Header;
use grandt\ResizeGif\Structure\ImageDescriptor;
use grandt\ResizeGif\Structure\LogicalScreenDescriptor;

/**
 * Create a gif file, typically an animation.
 *
 * Class CreateGif
 * @package ResizeGif
 */
class CreateGif {
    /**
     * No disposal specified.
     * The decoder is not required to take any action.
     */
    const DISPOSAL_NOT_SPECIFIED = 0;

    /**
     * Do not dispose.
     * The graphic is to be left in place.
     */
    const DISPOSAL_NONE = 1;

    /**
     * Restore to background color.
     * The area used by the graphic must be restored to the background color.
     */
    const DISPOSAL_RESTORE_TO_BG_COLOR = 2;

    /**
     * Restore to previous.
     * The decoder is required to restore the area overwritten by the graphic with what was there prior to rendering the graphic.
     */
    const DISPOSAL_RESTORE_TO_PREVIOUS = 3;

    private $toFile = false;
    private $width = 0;
    private $height = 0;
    private $dstFile = null;
    /**
     * @var FileHandler
     */
    private $fh = null;

    /**
     * @param int       $width            Target width of the new gif file.
     * @param int       $height           Target height of the new gif file.
     * @param int|array $bgColor          if an integer is the background color index, and only used with $globalColorTable. Else the $bgColor is an array with three 8-bit values, for indexes 'r', 'g', 'b'
     * @param string    $globalColorTable binary string with color data, 1 byte per channel, three channel per color in the order R, G, B. The table MUST contain either 2, 4, 8, 16, 32, 64, 128 or 256 colors. Pad with 0x00 bytes.
     * @param string    $dstFile          Filename to write to. If set to null (default) the gif will be streamed to the standard output.
     *
     * @throws Exception
     */
    public function __construct($width, $height, $bgColor = null, $globalColorTable = null, $dstFile = null) {
        $this->width = $width;
        $this->height = $height;
        $this->dstFile = $dstFile;

        if (isset($dstFile)) {
            $this->toFile = true;

            $this->fh = new FileHandler();
            try {
                if (is_file($dstFile)) {
                    unlink($dstFile);
                }
                $this->fh->openFile($dstFile, true);
            } catch (Exception $e) {
                throw $e;
            }
        }

        $this->write("GIF89a");
        $lsd = new LogicalScreenDescriptor();
        $lsd->screenWidth = $width;
        $lsd->screenHeight = $height;
        $lsd->colorResolution = 7;


        if (isset($bgColor)) {
            if (is_integer($bgColor) && isset($globalColorTable)) {
                $ctSize = BinStringStatic::_strlen($globalColorTable);
                $lsd->colorTableFlag = true;
                $lsd->colorTableSize = $ctSize / 3;
                $lsd->colorTable = $globalColorTable;
                $lsd->bgColorIndex = $bgColor;
            } else {
                if (is_array($bgColor)) {
                    $lsd->colorTableFlag = true;
                    $lsd->colorTableSize = 2;
                    $lsd->colorTable = chr($bgColor['r']) . chr($bgColor['g']) . chr($bgColor['b']) . "\x00\x00\x00";
                    $lsd->bgColorIndex = 0;
                }
            }
        }

        $this->write($lsd);
    }

    /**
     * Add an image frame to the gif file. This image will be without animation data.
     *
     * @param string $srcFile
     * @param int    $offsetLeft Offset of this frame from the left edge. Default 0.
     * @param int    $offsetTop  Offset of this frame from the top edge. Default 0.
     */
    public function addImage($srcFile, $offsetLeft = 0, $offsetTop = 0) {
        $this->addFrame($srcFile, -1, $offsetLeft, $offsetTop);
    }

    /**
     * Add an image frame to the gif file.
     *
     * @param string $srcFile
     * @param int    $delay          Frame duration delay in milliseconds. Default 60. Note: Gifs operate in 1/100 second intervals, meaning the value will be rounded. Use in increments of 10.
     * @param int    $offsetLeft     Offset of this frame from the left edge. Default 0.
     * @param int    $offsetTop      Offset of this frame from the top edge. Default 0.
     * @param int    $disposalMethod In animations with transparency on frames, the disposal needs to be taken into account.
     * @param bool   $userInput      Tell the decoder to wait for user interaction before continuing. The animation will continue when the user acts, or the delay timer runs out, whichever comes first. Not all decoders obey this flag.
     *
     * @throws Exception           If the provided file is not a valid image.
     */
    public function addFrame($srcFile, $delay = 60,
                             $offsetLeft = 0, $offsetTop = 0,
                             $disposalMethod = self::DISPOSAL_NOT_SPECIFIED,
                             $userInput = false) {

        $tFile = $srcFile;
        $fhT = new FileHandler();
        $fhT->openFile($tFile);
        $header = $fhT->readData(6);
        $fhT->closeFile();

        $isGif = $header === "GIF87a" || $header === "GIF89a";

        $cleanTempFile = false;
        if (!$isGif) {
            $image_p = imagecreatefromstring(file_get_contents($srcFile));
            if ($image_p !== false) {
                $tFile = tempnam("BewareOfGeeksBearingGifs", "grD");
                $cleanTempFile = true;
                imagegif($image_p, $tFile);
            } else {
                throw new Exception("Image error: File is not an image");
            }
        }

        $fhT->openFile($tFile, false);
        new Header($fhT);
        $lsd = new LogicalScreenDescriptor($fhT);
        $nGce = null;
        if ($delay >= 0) {
            $nGce = new GraphicControlExtension();
        }

        $nId = ImageDescriptor::getFirstImageDescriptor($fhT, $nGce);
        $fhT->closeFile();

        if ($cleanTempFile) {
            unset($tFile);
        }

        $nId->screenLeftPos = $offsetLeft;
        $nId->screenTopPos = $offsetTop;

        if (!$nId->colorTableFlag) {
            $nId->colorTableFlag = $lsd->colorTableFlag;
            $nId->colorTableSize = $lsd->colorTableSize;
            $nId->colorTable = $lsd->colorTable;
        }

        if ($delay >= 0) {
            $nGce->delayTime = (int)($delay / 10);
            $nGce->disposalMethod = $disposalMethod;
            $nGce->userInputFlag = $userInput;
            $nGce->imageDescriptor = $nId;

            $this->write($nGce);
        } else {
            $this->write($nId);
        }
    }

    public function closeGif() {
        $this->write("\x3b"); // Gif trailer.

        if ($this->toFile) {
            $this->fh->closeFile();
        }
    }

    /**
     * @param string|AbstractExtensionBlock $data
     */
    private function write($data) {
        if ($this->toFile) {
            if ($data instanceof AbstractExtensionBlock) {
                $data->encodeToFile($this->fh);
            } else {
                $this->fh->writeData($data);
            }
        } else {
            if ($data instanceof AbstractExtensionBlock) {
                print $data->encode($this->fh);
            } else {
                print $data;
            }
        }
    }

    public function addCommentBlock($comment) {
        $ceb = new CommentExtension();
        $pos = 0;
        $len = BinStringStatic::_strlen($comment);
        $ceb->dataSubBlocks = "";
        while ($len - $pos > 255) {
            $ceb->dataSubBlocks .= "\xff" . BinStringStatic::_substr($comment, $pos, 255);
            $pos += 255;
        }
        $ceb->dataSubBlocks .= chr($len - $pos) . BinStringStatic::_substr($comment, $pos);

        $this->write($ceb);
    }

    public function addNetscapeApplicationBlock() {
        $this->addApplicationBlock("NETSCAPE", "2.0", "\x03\x01\xff\xff");
    }

    public function addApplicationBlock($identifier, $authCode, $data) {
        $ade = new ApplicationExtension();
        $ade->applicationIdentifier = $identifier;
        $ade->applicationAuthenticationCode = $authCode;
        $ade->dataSubBlocks = $data;
        $this->write($ade);
    }
}
