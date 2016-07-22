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

class LogicalScreenDescriptor extends AbstractExtensionBlock {
    public $screenWidth;
    public $screenHeight;

    public $colorTableFlag = false;
    public $colorResolution = 0;
    public $sortFlag = false;
    public $colorTableSize = 2;
    public $bgColorIndex = 0;
    public $pixelAspectRatio;

    public $colorTable = "\x00\x00\x00\x00\x00\x00";

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
        $packedFields |= (($this->colorResolution << 4) & 0x70);
        $packedFields |= $this->sortFlag ? 0x08 : 0x00;

        // For just 8 possible values, the lookup table is faster.
        // the commented line below is the pedantic way of doing it.
        //$packedFields |= ceil(log($this->colorTableSize) / log(2)) - 1;
        $packedFields |= $this->ctSizeList[$this->colorTableSize];

        $r = DataHandler::packUint16($this->screenWidth)
            . DataHandler::packUint16($this->screenHeight)
            . chr($packedFields & 0xff)
            . chr($this->bgColorIndex)
            . chr($this->pixelAspectRatio == 0 ? 0 : (int)(($this->pixelAspectRatio * 64) / 15));

        if ($this->colorTableFlag && $this->colorTableSize > 0) {
            $r .= $this->colorTable;
        }

        return $r;
    }

    /**
     * @param FileHandler $fh
     *
     * @throws Exception
     */
    public function decode($fh) {
        if ($fh->getRemainingBytes() < 7) {
            throw new Exception("Insufficient data. Need 7 bytes, got " . $fh->getRemainingBytes());
        }
        $this->screenWidth = $fh->readUint16();
        $this->screenHeight = $fh->readUint16();

        $packedFields = ord($fh->readByte());

        $this->colorTableFlag = ($packedFields & 0x80) > 0;

        $this->colorResolution = ($packedFields & 0x70) >> 4;

        $this->sortFlag = ($packedFields & 0x08) > 0;

        // For just 8 possible values, the lookup table is faster.
        // the commented lines below is the pedantic way of doing it.
        // $v = ($packedFields & 0x07);
        // $this->colorTableSize = pow(2, $v + 1);
        $this->colorTableSize = $this->ctSizeListRev[$packedFields & 0x07];

        $this->bgColorIndex = $fh->readByteUint();

        $v = $fh->readByteUint();
        $this->pixelAspectRatio = $v > 0 ? (($v + 15) / 64) : 0;

        if ($this->colorTableFlag && $this->colorTableSize > 0) {
            $this->colorTable = $fh->readData($this->colorTableSize * 3);
        }
    }

    /**
     * @param float $ratio
     */
    public function resize($ratio) {
        $this->screenWidth = (int)round($this->screenWidth * $ratio);
        $this->screenHeight = (int)round($this->screenHeight * $ratio);
    }

    public function __clone() {
        $nLsd = new LogicalScreenDescriptor();

        $nLsd->screenWidth = $this->screenWidth;
        $nLsd->screenHeight = $this->screenHeight;

        $nLsd->colorTableFlag = $this->colorTableFlag;
        $nLsd->colorResolution = $this->colorResolution;
        $nLsd->sortFlag = $this->sortFlag;
        $nLsd->colorTableSize = $this->colorTableSize;
        $nLsd->bgColorIndex = $this->bgColorIndex;
        $nLsd->pixelAspectRatio = $this->pixelAspectRatio;

        $nLsd->colorTable = $this->colorTable;

        return $nLsd;
    }
}
