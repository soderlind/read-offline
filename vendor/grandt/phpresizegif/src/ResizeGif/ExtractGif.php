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

class ExtractGif {

    /**
     * Extract gif frames, not the animation, just the frame components. Animation details are retained though.
     *
     * @param string $srcFile
     * @param string $dstDir
     * @param string $dstFilePrefix
     *
     * @throws Exception
     */
    public static function extract($srcFile, $dstDir, $dstFilePrefix = null) {
        $fh = new FileHandler();

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

        $dstDir = str_replace("\\", "/", $dstDir);
        if ($dstFilePrefix == null) {
            $dstFilePrefix = basename($srcFile, '.gif');
        }
        $lc = substr($dstDir, -1);
        if ("/" !== $lc) {
            $dstDir .= "/";
        }

        $dstPath = $dstDir . $dstFilePrefix . "_";

        $lsd = new LogicalScreenDescriptor($fh);

        self::processGifStream($fh, $lsd, $dstPath);
        $fh->closeFile();
    }

    /**
     * @param FileHandler             $fh
     * @param LogicalScreenDescriptor $lsd
     * @param string                  $dstPath
     */
    private static function processGifStream($fh, $lsd, $dstPath) {
        $frameCount = 0;
        while (!$fh->isEOF()) {
            switch (ord($fh->peekByte())) {
                case AbstractExtensionBlock::CONTROL_EXTENSION:
                    self::readExtensionBlock($fh, $frameCount, $lsd, $dstPath);
                    break;
                case AbstractExtensionBlock::CONTROL_IMAGE:
                    $idb = new ImageDescriptor($fh);
                    self::writeFrame($dstPath, $frameCount, $lsd, $idb);
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
    }


    /**
     * @param FileHandler             $fh
     * @param int                     $frameCount
     * @param LogicalScreenDescriptor $lsd
     * @param string                  $dstPath
     */
    private static function readExtensionBlock($fh, &$frameCount, $lsd, $dstPath) {

        $fh->seekForward(1);
        $blockLabel = $fh->peekByte();

        switch (ord($blockLabel)) {
            case AbstractExtensionBlock::LABEL_APPLICATION:
                new ApplicationExtension($fh);

                break;
            case AbstractExtensionBlock::LABEL_COMMENT:
                new CommentExtension($fh);

                break;
            case AbstractExtensionBlock::LABEL_GRAPHICS_CONTROL:
                $gce = new GraphicControlExtension($fh);
                self::writeFrame($dstPath, $frameCount, $lsd, $gce);

                break;
            case AbstractExtensionBlock::LABEL_PLAIN_TEXT:
                new PlainTextExtension($fh);

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
     * @param string                  $dstPath
     * @param int                     $frameCount
     * @param LogicalScreenDescriptor $lsd
     * @param AbstractExtensionBlock  $aeb
     *
     * @throws Exception
     */
    private static function writeFrame($dstPath, &$frameCount, $lsd, $aeb) {
        $frameCount++;
        if ($dstPath !== null) {
            $dstFileName = $dstPath . str_pad(strval($frameCount), 3, "0", STR_PAD_LEFT) . ".gif";
            $fhW = new FileHandler();
            $fhW->openFile($dstFileName, true);
            $fhW->writeData("GIF89a");
            $lsd->encodeToFile($fhW);
            $aeb->encodeToFile($fhW);
            $fhW->writeData("\x3b");
            $fhW->closeFile();
        }
    }
}
