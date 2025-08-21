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
namespace grandt\ResizeGif\Files;

use com\grandt\BinStringStatic;
use Exception;

/**
 * Wrapping file access, helping the user to enforce endianess of the files read or written.
 */
class FileHandler {
    const LITTLE_ENDIAN = DataHandler::LITTLE_ENDIAN;
    const BIG_ENDIAN = DataHandler::BIG_ENDIAN;

    /**
     * @var int prevailing endianess of the opened file. Defaults to Little Endian (Intel, and most of the web standards)
     */
    private $endianess = self::LITTLE_ENDIAN;

    /**
     * @var resource active file handle
     */
    private $handle;

    /**
     * @var string name of the opened file.
     */
    private $filename;

    /**
     * @var bool is the file writable?
     */
    private $isWritable = false;

    public function __construct($endianess = self::LITTLE_ENDIAN) {
        $this->endianess = $endianess;
    }

    public function __destruct() {
        if (isset($this->handle)) {
            $this->closeFile();
        }
    }

    /**
     * Open a file for reading
     *
     * @param string $filename
     * @param bool   $isWritable
     * @param int    $endianess
     *
     * @throws Exception
     */
    public function openFile($filename, $isWritable = false, $endianess = -1) {
        if (isset($this->handle)) {
            $this->closeFile();
        }

        $this->filename = $filename;

        if (is_file($filename)) {
            $this->handle = fopen($filename, $isWritable ? "r+b" : "rb");
        } else {
            if ($isWritable) {
                $this->handle = fopen($filename, $isWritable ? "x+b" : "rb");
            } else {
                throw new Exception("File not found");
            }
        }

        $this->endianess = $this->getTargetEndianess($endianess);
        $this->isWritable = $isWritable;
    }

    /**
     * Close the file
     */
    public function closeFile() {
        if (isset($this->handle)) {
            if ($this->isWritable) {
                fflush($this->handle);
            }
            fclose($this->handle);
        }
        $this->filename = null;
        $this->handle = null;
        $this->endianess = self::LITTLE_ENDIAN;
        $this->isWritable = false;
    }

    /**
     * @return resource
     */
    public function getHandle() {
        return $this->handle;
    }

    /**
     * Rewind the file pointer location by $length bytes.
     *
     * @param integer $length
     *
     * @return int the new position in the file.
     */
    public function seekReverse($length) {
        if (isset($this->handle)) {
            if ($length < 0) {
                $length = -$length;
            }
            if ($this->getPointer() + $length < 0) {
                $length = $this->getPointer();
            }
            fseek($this->handle, -$length, SEEK_CUR);

            return $this->getPointer();
        }

        return 0;
    }

    /**
     * Forward the file pointer location by $length bytes.
     *
     * @param integer $length
     *
     * @return int the new position in the file.
     */
    public function seekForward($length) {
        if ($length < 0) {
            return $this->seekReverse(-$length);
        }

        if (isset($this->handle)) {
            if ($this->getPointer() + $length > $this->getLength()) {
                $length = $this->getRemainingBytes();
            }
            fseek($this->handle, $length, SEEK_CUR);

            return $this->getPointer();
        }

        return 0;
    }

    /**
     * Set the file pointer location.
     *
     * @param integer $position
     *
     * @return int the new position in the file.
     */
    public function seekAbs($position) {
        if (isset($this->handle)) {
            if ($position > $this->getLength()) {
                $position = $this->getLength();
            } elseif ($position < 0) {
                $position = 0;
            }
            fseek($this->handle, $position, SEEK_SET);

            return $this->getPointer();
        }

        return 0;
    }

    /**
     * Set the file pointer location at the end of the file.
     *
     * @param integer $position
     *
     * @return int the new position in the file.
     */
    public function seekEnd($position = 0) {
        if (isset($this->handle)) {
            if ($position < 0) {
                $position = -$position;
            }
            if ($position > $this->getLength()) {
                $position = $this->getLength();
            }
            fseek($this->handle, -$position, SEEK_END);

            return $this->getPointer();
        }

        return 0;
    }

    /**
     * Set the file pointer location. (set it to 0).
     */
    public function rewind() {
        if (isset($this->handle)) {
            rewind($this->handle);
        }
    }

    /**
     * Get a section of the data from a pointer location. The current pointer will be retained.
     *
     * @param integer $dataLocationStart
     * @param integer $dataLength
     *
     * @return string
     */
    public function absReadData($dataLocationStart, $dataLength) {
        if (isset($this->handle) && $dataLocationStart < $this->getLength()) {
            $pointer = ftell($this->handle);
            fseek($this->handle, $dataLocationStart, SEEK_SET);
            $data = fread($this->handle, $dataLength);
            fseek($this->handle, $pointer, SEEK_SET);

            return $data;
        }

        return "";
    }

    /**
     * Get a section of the data from the current pointer location.
     *
     * @param integer $dataLength
     *
     * @return string
     */
    public function readData($dataLength) {
        if (isset($this->handle)) {
            return fread($this->handle, $dataLength);
        }

        return "";
    }


    /**
     * Read a data block up to 255 bytes.
     * This functions assumes that the first byte after the skipped bytes is the length.
     * Be <b>very</b> careful with this one.
     *
     * @param integer $skipBytes
     *
     * @return string
     */
    public function readDataBlock($skipBytes = 0) {
        if (isset($this->handle)) {
            $this->seekForward($skipBytes);
            $length = $this->readByteUint();

            if ($length != 0) {
                return $this->readData($length);
            }
        }

        return "";
    }

    /**
     * Read the byte from the current pointer location, without moving the pointer.
     *
     * @return string
     */
    public function peekByte() {
        $b = $this->readData(1);
        $this->seekReverse(1);

        return $b;
    }

    /**
     * Read a single byte
     *
     * @return string
     */
    public function readByte() {
        return $this->readData(1);
    }

    /**
     * Read a byte and return ASCII value
     *
     * @return integer
     */
    public function readByteUint() {
        if (isset($this->handle)) {
            return ord($this->readByte());
        }

        return 0;
    }

    /**
     * Read a two byte 16-bit signed short.
     *
     * @param int $endianess read number with specified endianess, defaults to the file endianess.
     *
     * @return int
     */
    public function readInt16($endianess = -1) {
        return DataHandler::uint2Int16($this->readUint16($endianess));
    }

    /**
     * Read a two byte 16-bit UNSIGNED short.
     *
     * @param int $endianess read number with specified endianess, defaults to the file endianess.
     *
     * @return int
     */
    public function readUint16($endianess = -1) {
        if (isset($this->handle)) {
            return DataHandler::unpackUint16(fread($this->handle, 2), $this->getTargetEndianess($endianess));
        }

        return 0;
    }

    /**
     * Read a four byte 32-bit signed int.
     *
     * @param int $endianess read number with specified endianess, defaults to the file endianess.
     *
     * @return int
     */
    public function readInt32($endianess = -1) {
        return DataHandler::uint2Int32($this->readUint32($endianess));
    }

    /**
     * Read a four byte 32-bit UNSIGNED int.
     *
     * @param int $endianess read number with specified endianess, defaults to the file endianess.
     *
     * @return int
     */
    public function readUint32($endianess = -1) {
        if (isset($this->handle)) {
            return DataHandler::unpackUint32(fread($this->handle, 4), $this->getTargetEndianess($endianess));
        }

        return 0;
    }

    /**
     * Read an eight byte 64-bit signed long.
     *
     * @param int $endianess read number with specified endianess, defaults to the file endianess.
     *
     * @return int
     */
    public function readInt64($endianess = -1) {
        return DataHandler::uint2Int64($this->readUint64($endianess));
    }

    /**
     * Read an eight byte 64-bit UNSIGNED long.
     *
     * @param int $endianess read number with specified endianess, defaults to the file endianess.
     *
     * @return int
     */
    public function readUint64($endianess = -1) {
        if (isset($this->handle)) {
            return DataHandler::unpackUint64(fread($this->handle, 8), $this->getTargetEndianess($endianess));
        }

        return 0;
    }

    /**
     * Write a section of the data from the current pointer location.
     *
     * @param $data
     *
     * @return int
     */
    public function writeData($data) {
        if (isset($this->handle) && $this->isWritable) {
            return fwrite($this->handle, $data);
        }

        return 0;
    }

    /**
     * Write a uint value as a byte
     *
     * @return integer
     */
    public function writeByteUint($number) {
        if (isset($this->handle) && $this->isWritable) {
            return $this->writeData($this->handle, chr($number & 0xff));
        }

        return 0;
    }

    /**
     * Write a two byte 16-bit signed short.
     *
     * @param     $number
     * @param int $endianess Write number with specified endianess, defaults to the file endianess.
     */
    public function writeInt16($number, $endianess = -1) {
        if (isset($this->handle) && $this->isWritable) {
            $this->writeData(DataHandler::packInt16($number, $this->getTargetEndianess($endianess)));
        }
    }

    /**
     * Write a four byte 32-bit signed int.
     *
     * @param     $number
     * @param int $endianess Write number with specified endianess, defaults to the file endianess.
     */
    public function writeInt32($number, $endianess = -1) {
        if (isset($this->handle) && $this->isWritable) {
            $this->writeData(DataHandler::packInt32($number, $this->getTargetEndianess($endianess)));
        }
    }

    /**
     * Write an eight byte 64-bit signed long.
     *
     * @param     $number
     * @param int $endianess Write number with specified endianess, defaults to the file endianess.
     */
    public function writeInt64($number, $endianess = -1) {
        if (isset($this->handle) && $this->isWritable) {
            $this->writeData(DataHandler::packUint64($number, $this->getTargetEndianess($endianess)));
        }
    }

    /**
     * Write a two byte 16-bit signed short.
     *
     * @param     $number
     * @param int $endianess Write number with specified endianess, defaults to the file endianess.
     */
    public function writeUint16($number, $endianess = -1) {
        if (isset($this->handle) && $this->isWritable) {
            $this->writeData(DataHandler::packInt16($number, $this->getTargetEndianess($endianess)));
        }
    }

    /**
     * Write a four byte 32-bit signed int.
     *
     * @param     $number
     * @param int $endianess Write number with specified endianess, defaults to the file endianess.
     */
    public function writeUint32($number, $endianess = -1) {
        if (isset($this->handle) && $this->isWritable) {
            $this->writeData(DataHandler::packUint32($number, $this->getTargetEndianess($endianess)));
        }
    }

    /**
     * Write an eight byte 64-bit signed long.
     *
     * @param     $number
     * @param int $endianess Write number with specified endianess, defaults to the file endianess.
     */
    public function writeUint64($number, $endianess = -1) {
        if (isset($this->handle) && $this->isWritable) {
            $this->writeData(DataHandler::packUint64($number, $this->getTargetEndianess($endianess)));
        }
    }

    /**
     * Compare $byte with the current pointer location without moving the pointer.
     *
     * @param string $byte
     *
     * @return boolean
     */
    public function compareByte($byte) {
        if (isset($this->handle)) {
            return $this->peekByte() === $byte;
        }

        return false;
    }

    /**
     * Compare $byteSequence with the current pointer location without moving the pointer.
     *
     * @param string $byteSequence
     *
     * @return boolean
     */
    public function compareBytes($byteSequence) {
        if (isset($this->handle)) {
            $length = BinStringStatic::_strlen($byteSequence);

            $data = $this->readData($length);
            $this->seekReverse($length);

            return $data === $byteSequence;
        }

        return false;
    }

    /**
     * Check the end of the file
     *
     * @return boolean
     */
    public function isEOF() {
        return isset($this->handle) && (feof($this->handle) || $this->getPointer() === $this->getLength());
    }

    /**
     * Read the current pointer location.
     *
     * @return int
     */
    public function getPointer() {
        if (isset($this->handle)) {
            return ftell($this->handle);
        }

        return 0;
    }

    /**
     * Read the current pointer location.
     *
     * @return int
     */
    public function getLength() {
        if (isset($this->handle)) {
            $stat = fstat($this->handle);

            return $stat['size'];
        }

        return 0;
    }

    /**
     * Read the number of remaining bytes in the file, seen from the current pointer location.
     *
     * @return int
     */
    public function getRemainingBytes() {
        if (isset($this->handle)) {
            return $this->getLength() - ftell($this->handle);
        }

        return 0;
    }

    /**
     * @param $endianess
     *
     * @return int
     */
    private function getTargetEndianess($endianess = -1) {
        return $endianess == -1 ? $this->endianess : $endianess;
    }
}
