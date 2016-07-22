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
use grandt\ResizeGif\Files\FileHandler;

class CommentExtension extends AbstractExtensionBlock {
    public $blockLabel = self::LABEL_COMMENT;

    /**
     * @param FileHandler $fh
     */
    public function decode($fh) {
        $fh->seekForward(1); // skip block label.

        $this->readDataSubBlocks($fh);

        while ($fh->peekByte() == "\x00") {
            $fh->seekForward(1);
        }
    }

    /**
     * @return string
     */
    public function encode() {
        return "\x21" . chr($this->blockLabel) . $this->dataSubBlocks . "\x00";
    }

    public function getComment() {
        $pos = 0;
        $len = BinStringStatic::_strlen($this->dataSubBlocks);
        $comment = "";
        while ($pos < $len && ord($this->dataSubBlocks[$pos]) > 0) {
            $comment .= BinStringStatic::_substr($this->dataSubBlocks, $pos + 1, ord($this->dataSubBlocks[$pos]));
            $pos += 1 + ord($this->dataSubBlocks[$pos]);
        }

        return $comment;
    }
}
