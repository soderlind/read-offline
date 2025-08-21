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
namespace grandt\ResizeGif\Structure;

use com\grandt\BinStringStatic;
use Exception;
use grandt\ResizeGif\Files\DataHandler;
use grandt\ResizeGif\Files\FileHandler;
use grandt\ResizeGif\Files\ImageHandler;

class ImageDescriptor extends AbstractExtensionBlock {
    /**
     * @var GraphicControlExtension
     */
    public $parentGCE = null;

    public $screenLeftPos;
    public $screenTopPos;
    public $screenWidth;
    public $screenHeight;

    public $colorTableFlag;
    public $interlaceFlag;
    public $sortFlag;
    public $reserved;
    public $colorTableSize;
    public $colorTable = "";

    public $lzwMinCodeSize;

    /**
     * @return string
     */
    public function encode() {
        $ctSize = BinStringStatic::_strlen($this->colorTable);
        if ($ctSize > 0) {
            $this->colorTableFlag = true;
            $this->colorTableSize = $ctSize / 3;
        } else {
            $this->colorTableFlag = false;
            $this->colorTableSize = 2;
        }

        $packedFields = 0;
        $packedFields |= $this->colorTableFlag ? 0x80 : 0x00;
        $packedFields |= $this->interlaceFlag ? 0x40 : 0x00;
        $packedFields |= $this->sortFlag ? 0x20 : 0x00;
        $packedFields |= ($this->reserved << 3) & 0x18;
        $packedFields |= ceil(log($this->colorTableSize) / log(2)) - 1;
        //$packedFields |= $this->ctSizeList[$this->colorTableSize];
        // The lookup table is faster, but I wanted the Pedantic version.

        $r = DataHandler::packUint16($this->screenLeftPos)
            . DataHandler::packUint16($this->screenTopPos)
            . DataHandler::packUint16($this->screenWidth)
            . DataHandler::packUint16($this->screenHeight)
            . chr($packedFields & 0xff);

        if ($this->colorTableFlag && $this->colorTableSize > 0) {
            $r .= $this->colorTable;
        }

        return "\x2c" . $r . $this->lzwMinCodeSize . $this->dataSubBlocks . "\x00";
    }

    /**
     * @param FileHandler $fh
     */
    public function decode($fh) {
        $fh->seekForward(1);
        $this->screenLeftPos = $fh->readUint16();
        $this->screenTopPos = $fh->readUint16();
        $this->screenWidth = $fh->readUint16();
        $this->screenHeight = $fh->readUint16();

        $packedFields = ord($fh->readByte());

        $this->colorTableFlag = ($packedFields & 0x80) > 0;
        $this->interlaceFlag = ($packedFields & 0x40) > 0;
        $this->sortFlag = ($packedFields & 0x20) > 0;
        $this->reserved = ($packedFields & 0x18) >> 3;
        $ctSize = ($packedFields & 0x07);
        $this->colorTableSize = pow(2, $ctSize + 1);

        $this->colorTable = "";

        if ($this->colorTableFlag && $this->colorTableSize > 0) {
            $this->colorTable = $fh->readData($this->colorTableSize * 3);
        }

        // Here comes the image frame:
        $this->lzwMinCodeSize = $fh->readByte();

        // After the Min Code Size, the LZW stream behaves similarly to the data sub-blocks, and the data will be stored in that variable.
        $this->readDataSubBlocks($fh);

        // Spool past the \x00 terminator byte. There *should* only be one.
        while ($fh->peekByte() == "\x00") {
            $fh->seekForward(1);
        }
    }

    /**
     * @return ImageDescriptor
     */
    public function __clone() {
        $nId = new ImageDescriptor();

        $nId->screenLeftPos = $this->screenLeftPos;
        $nId->screenTopPos = $this->screenTopPos;
        $nId->screenWidth = $this->screenWidth;
        $nId->screenHeight = $this->screenHeight;

        $nId->colorTableFlag = $this->colorTableFlag;
        $nId->interlaceFlag = $this->interlaceFlag;
        $nId->sortFlag = $this->sortFlag;
        $nId->reserved = $this->reserved;
        $nId->colorTableSize = $this->colorTableSize;
        $nId->colorTable = $this->colorTable;

        $nId->lzwMinCodeSize = $this->lzwMinCodeSize;
        $nId->dataSubBlocks = $this->dataSubBlocks;

        return $nId;
    }

    /**
     * @param LogicalScreenDescriptor $lsd if the parent is an animation, the main LSD may contain vital information.
     *
     * @return string
     */
    public function generateGif($lsd) {
        $nLsd = clone $lsd;
        $nLsd->screenWidth = $this->screenWidth;
        $nLsd->screenHeight = $this->screenHeight;

        $nGce = null;
        if ($this->parentGCE != null) {
            $nGce = clone $this->parentGCE;
            $nGce->disposalMethod = 0;
            $nGce->userInputFlag = false;
            $nGce->delayTime = 0;

            $nId = $nGce->imageDescriptor;
        } else {
            $nId = clone $this;
        }
        $nId->screenLeftPos = 0;
        $nId->screenTopPos = 0;
        $nId->screenWidth = $this->screenWidth;
        $nId->screenHeight = $this->screenHeight;

        return "GIF89a" . $nLsd->encode()
        . ($nGce != null ? $nGce->encode() : $nId->encode())
        . chr(AbstractExtensionBlock::CONTROL_TRAILER);
    }

    /**
     * @param                         $ratio
     * @param LogicalScreenDescriptor $lsd
     *
     * @throws Exception
     */
    public function resize($ratio, $lsd) {
        $scrLp = $this->screenLeftPos;
        $scrTp = $this->screenTopPos;

        $tFileS = tempnam("BewareOfGeeksBearingGifs", "grS");
        $tFileD = tempnam("BewareOfGeeksBearingGifs", "grD");

        $fhT = new FileHandler();
        $fhT->openFile($tFileS, true);
        $fhT->writeData($this->generateGif($lsd));
        $fhT->closeFile();

        ImageHandler::resizeGif($tFileS, $tFileD, $ratio, true);

        $fhT->openFile($tFileD, false);
        new Header($fhT);
        $nLsd = new LogicalScreenDescriptor($fhT);

        $nId = self::getFirstImageDescriptor($fhT, $this->parentGCE);
        $fhT->closeFile();

        unset($tFileD);
        unset($tFileS);

        $this->screenLeftPos = (int)round($scrLp * $ratio);
        $this->screenTopPos = (int)round($scrTp * $ratio);
        $this->screenWidth = $nId->screenWidth;
        $this->screenHeight = $nId->screenHeight;

        $this->reserved = $nId->reserved;

        $this->interlaceFlag = $nId->interlaceFlag;
        $this->sortFlag = $nLsd->sortFlag;

        $this->colorTableFlag = $nLsd->colorTableFlag;
        $this->colorTableSize = $nLsd->colorTableSize;
        $this->colorTable = $nLsd->colorTable;

        $this->lzwMinCodeSize = $nId->lzwMinCodeSize;
        $this->dataSubBlocks = $nId->dataSubBlocks;
    }

    /**
     * @param FileHandler             $fh
     * @param GraphicControlExtension $gce
     *
     * @return ImageDescriptor
     */
    public static function getFirstImageDescriptor($fh, $gce = null) {
        $id = self::getFirstImageFrame($fh, $gce);
        if ($id instanceof GraphicControlExtension) {
            return $id->imageDescriptor;
        }

        return $id;
    }

    /**
     * @param FileHandler             $fh
     * @param GraphicControlExtension $gce
     *
     * @return ImageDescriptor|GraphicControlExtension
     */
    public static function getFirstImageFrame($fh, $gce = null) {
        $nId = null;

        while ($nId == null && !$fh->isEOF()) {
            $blockLabel = $fh->peekByte();

            if (ord($blockLabel) == AbstractExtensionBlock::CONTROL_IMAGE) {
                $nId = new ImageDescriptor($fh);
                if ($gce != null) {
                    $gce->transparentColorFlag = false;
                    $gce->transparentColorIndex = 0;
                }

                return $nId;
            } else {
                if (ord($blockLabel) == AbstractExtensionBlock::CONTROL_EXTENSION) {
                    $fh->seekForward(1);
                    $blockLabel = $fh->peekByte();
                    if (ord($blockLabel) == AbstractExtensionBlock::LABEL_GRAPHICS_CONTROL) {
                        $nGce = new GraphicControlExtension($fh);
                        if ($gce != null) {
                            $gce->transparentColorFlag = $nGce->transparentColorFlag;
                            $gce->transparentColorIndex = $nGce->transparentColorIndex;
                        }

                        return $nGce;
                    }
                }
            }
        }

        return null;
    }
}
