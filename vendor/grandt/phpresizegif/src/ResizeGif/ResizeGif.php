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

use Exception;
use grandt\ResizeGif\Files\FileHandler;
use grandt\ResizeGif\Structure\AbstractExtensionBlock;
use grandt\ResizeGif\Structure\ApplicationExtension;
use grandt\ResizeGif\Structure\CommentExtension;
use grandt\ResizeGif\Structure\GraphicControlExtension;
use grandt\ResizeGif\Structure\Header;
use grandt\ResizeGif\Structure\ImageDescriptor;
use grandt\ResizeGif\Structure\LogicalScreenDescriptor;
use grandt\ResizeGif\Structure\PlainTextExtension;

class ResizeGif {

    /**
     * Resize the gif to a specific width. The height will be scaled in proportion, maintaining the aspect ratio.
     *
     * @param string $srcFile
     * @param string $dstFile the destination file. This will get overwritten if it already exists.
     * @param int    $width
     *
     * @throws Exception
     */
    public static function ResizeToWidth($srcFile, $dstFile, $width) {
        try {
            $size = self::getSize($srcFile);
        } catch (Exception $e) {
            throw $e;
        }

        $ratio = $width / $size["width"];
        self::ResizeByRatio($srcFile, $dstFile, $ratio);
    }

    /**
     * Resize the gif to a specific height. The width will be scaled in proportion, maintaining the aspect ratio.
     *
     * @param string $srcFile
     * @param string $dstFile the destination file. This will get overwritten if it already exists.
     * @param int    $height
     *
     * @throws Exception
     */
    public static function ResizeToHeight($srcFile, $dstFile, $height) {
        try {
            $size = self::getSize($srcFile);
        } catch (Exception $e) {
            throw $e;
        }

        $ratio = $height / $size["height"];
        self::ResizeByRatio($srcFile, $dstFile, $ratio);
    }

    /**
     * Resize the gif by a ratio. The height and width will be scaled in proportion, maintaining the aspect ratio.
     * Example: A ratio of 0.5 will half the size, a ratio of 2 will double it.
     *
     * @param string $srcFile
     * @param string $dstFile the destination file. This will get overwritten if it already exists.
     * @param float  $ratio
     *
     * @throws Exception
     */
    public static function ResizeByRatio($srcFile, $dstFile, $ratio) {
        $fh = new FileHandler();
        $fhW = new FileHandler();

        try {
            $fh->openFile($srcFile);
        } catch (Exception $e) {
            throw $e;
        }

        $header = new Header($fh);

        if ($header->signature !== "GIF" && $header->version !== "87a" && $header->version !== "89a") {
            $fh->closeFile();
            throw new Exception("Not a gif file.");
        }

        try {
            if (is_file($dstFile)) {
                unlink($dstFile);
            }
            $fhW->openFile($dstFile, true);
        } catch (Exception $e) {
            throw $e;
        }

        $fhW->writeData("GIF89a");
        $lsd = new LogicalScreenDescriptor($fh);

        $lsd->resize($ratio);
        $lsd->encodeToFile($fhW);

        self::processGifStream($fh, $fhW, $ratio, $lsd);

        $fh->closeFile();
        $fhW->closeFile();
    }

    /**
     * @param string $file
     *
     * @return array with "width" and "height" of the gif file.
     * @throws Exception
     */
    public static function getSize($file) {
        $fh = new FileHandler();
        try {
            $fh->openFile($file);
        } catch (Exception $e) {
            throw $e;
        }

        $header = new Header($fh);

        if ($header->signature !== "GIF" && $header->version !== "87a" && $header->version !== "89a") {
            $fh->closeFile();
            throw new Exception("Not a gif file.");
        }
        $size = array(
            "width"  => $fh->readUint16(),
            "height" => $fh->readUint16());
        $fh->closeFile();

        return $size;
    }

    /**
     * @param FileHandler             $fh
     * @param FileHandler             $fhW
     * @param float                   $ratio
     * @param LogicalScreenDescriptor $lsd
     */
    private static function processGifStream($fh, $fhW, $ratio, $lsd) {
        while (!$fh->isEOF()) {
            switch (ord($fh->peekByte())) {
                case AbstractExtensionBlock::CONTROL_EXTENSION:
                    self::readExtensionBlock($fh, $fhW, $ratio, $lsd);
                    break;
                case AbstractExtensionBlock::CONTROL_IMAGE:
                    $idb = new ImageDescriptor($fh);
                    $idb->resize($ratio, $lsd);
                    $idb->encodeToFile($fhW);
                    break;
                case AbstractExtensionBlock::CONTROL_TRAILER:
                    $fh->seekForward(1);
                    break;
                case AbstractExtensionBlock::CONTROL_TERMINATOR:
                    $fh->seekForward(1);
                    break;
                default:
                    $fh->seekForward(1);
            }
        }

        $fhW->writeData(chr(AbstractExtensionBlock::CONTROL_TRAILER));
    }


    /**
     * @param FileHandler             $fh
     * @param FileHandler             $fhW
     * @param float                   $ratio
     * @param LogicalScreenDescriptor $lsd
     */
    private static function readExtensionBlock($fh, $fhW, $ratio, $lsd) {

        $fh->seekForward(1);
        $blockLabel = $fh->peekByte();

        switch (ord($blockLabel)) {
            case AbstractExtensionBlock::LABEL_APPLICATION:
                $adb = new ApplicationExtension($fh);
                $adb->encodeToFile($fhW);

                break;
            case AbstractExtensionBlock::LABEL_COMMENT:
                $ceb = new CommentExtension($fh);
                $ceb->encodeToFile($fhW);

                break;
            case AbstractExtensionBlock::LABEL_GRAPHICS_CONTROL:
                $gce = new GraphicControlExtension($fh);
                self::resizeGraphicControlExtensionBlock($gce, $ratio, $fhW, $lsd);

                break;
            case AbstractExtensionBlock::LABEL_PLAIN_TEXT:
                $pte = new PlainTextExtension($fh);
                $pte->encodeToFile($fhW);

                break;
            case AbstractExtensionBlock::CONTROL_TRAILER:
                break;
            default:
                $fh->seekForward(1);
                while (!$fh->compareByte("\x00")) {
                    $fh->seekForward(1);
                }
        }

        if ($fh->compareByte("\x00")) {
            $fh->readByte();
        }
    }

    /**
     * @param GraphicControlExtension $gce
     * @param float                   $ratio
     * @param FileHandler             $fhW
     * @param LogicalScreenDescriptor $lsd
     *
     * @throws Exception
     */
    private static function resizeGraphicControlExtensionBlock($gce, $ratio, $fhW, $lsd) {
        $id = $gce->imageDescriptor;

        $id->resize($ratio, $lsd, $gce);

        $gce->encodeToFile($fhW);
    }
}
