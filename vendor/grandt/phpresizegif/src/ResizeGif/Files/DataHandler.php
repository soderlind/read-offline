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

define('SYS_BIG_ENDIAN', pack('L', 1) === pack('N', 1));

/*
 * Pack: http://php.net/manual/en/function.pack.php
 * s 	signed short (always 16 bit, machine byte order)
 * S 	unsigned short (always 16 bit, machine byte order)
 * n 	unsigned short (always 16 bit, big endian byte order)
 * v 	unsigned short (always 16 bit, little endian byte order)
 * i 	signed integer (machine dependent size and byte order)
 * I 	unsigned integer (machine dependent size and byte order)
 * l 	signed long (always 32 bit, machine byte order)
 * L 	unsigned long (always 32 bit, machine byte order)
 * N 	unsigned long (always 32 bit, big endian byte order)
 * V 	unsigned long (always 32 bit, little endian byte order)
 * q 	signed long long (always 64-bit, machine byte order)         // 64-bit functions added in 5.6.3
 * Q 	unsigned long long (always 64-bit, machine byte order)       // 64-bit functions added in 5.6.3
 * J 	unsigned long long (always 64-bit, big endian byte order)    // 64-bit functions added in 5.6.3
 * P 	unsigned long long (always 64-bit, little endian byte order) // 64-bit functions added in 5.6.3
 */

/**
 * PHP doesn't supply pack codes for endian specific encoding on SIGNED integers,
 * and to be frank, doesn't appear to work correctly for UNSIGNED integers as well,
 * as they will, and do encode those as signed on some systems.
 *
 * May whomever thought it was a good idea to provide packing codes for machine specific
 * length and endianess feel the pain and misery of every developer having to deal with it,
 * for all eternity.
 */
class DataHandler {
    const LITTLE_ENDIAN = 0;
    const BIG_ENDIAN = 1;

    const NUMBER_SHORT = 16;
    const NUMBER_INT = 32;
    const NUMBER_LONG = 64;

    const INT16_HIGH_MAP = 0xff00;
    const INT16_LOW_MAP = 0x00ff;

    const INT32_HIGH_MAP = 0xffff0000;
    const INT32_LOW_MAP = 0x0000ffff;

    const INT64_HIGH_MAP = 0xffffffff00000000;
    const INT64_LOW_MAP = 0x00000000ffffffff;


    /**
     * Pack a signed 16-bit integer with a specific endianess.
     *
     * @param     $number
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return string
     */
    public static function packInt16($number, $endianess = self::LITTLE_ENDIAN) {
        $s = pack('s', $number);
        if ((SYS_BIG_ENDIAN ? self::BIG_ENDIAN : self::LITTLE_ENDIAN) != $endianess) {
            return strrev($s);
        }

        return $s;
    }

    /**
     * Pack a signed 16-bit integer with a specific endianess.
     *
     * @param     $number
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return string
     */
    public static function packUint16($number, $endianess = self::LITTLE_ENDIAN) {
        $ps = $endianess == self::LITTLE_ENDIAN ? 'v' : 'n';

        return pack($ps, ($number));
    }

    /**
     * Unpack a signed 16-bit integer with a specific endianess.
     *
     * @param     $data
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return int
     */
    public static function unpackInt16($data, $endianess = self::LITTLE_ENDIAN) {
        return self::uint2Int16(self::unpackUint16($data, $endianess));
    }

    /**
     * Unpack an unsigned 16-bit integer with a specific endianess.
     *
     * @param     $data
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return int
     */
    public static function unpackUint16($data, $endianess = self::LITTLE_ENDIAN) {
        $ps = $endianess == self::LITTLE_ENDIAN ? 'v' : 'n';
        $out = unpack($ps, $data);

        return $out[1];
    }


    /**
     * Pack a 32-bit integer with a specific endianess.
     *
     * @param     $number
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return string
     */
    public static function packInt32($number, $endianess = self::LITTLE_ENDIAN) {
        $s = pack('l', $number);
        if ((SYS_BIG_ENDIAN ? self::BIG_ENDIAN : self::LITTLE_ENDIAN) != $endianess) {
            return strrev($s);
        }

        return $s;
    }

    /**
     * Pack a 32-bit integer with a specific endianess.
     *
     * @param     $number
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return string
     */
    public static function packUint32($number, $endianess = self::LITTLE_ENDIAN) {
        $ps = $endianess == self::LITTLE_ENDIAN ? 'V' : 'N';

        return pack($ps, $number);
    }

    /**
     * Unpack a signed 32-bit integer with a specific endianess.
     *
     * @param     $data
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return int
     */
    public static function unpackInt32($data, $endianess = self::LITTLE_ENDIAN) {
        return self::uint2Int32(self::unpackUint32($data, $endianess));
    }

    /**
     * Unpack an unsigned 32-bit integer with a specific endianess.
     *
     * @param     $data
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return int
     */
    public static function unpackUint32($data, $endianess = self::LITTLE_ENDIAN) {
        $ps = $endianess == self::LITTLE_ENDIAN ? 'V' : 'N';
        $out = unpack($ps, $data);

        return $out[1];
    }


    /**
     * Pack a signed 64-bit integer with a specific endianess.
     *
     * On 32-bit systems, this will only pack the lower 4 bytes, padding the output to 8
     *
     * @param     $number
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return string
     * @throws Exception Exception 32-bit systems do not support 64-bit encoding.
     */
    public static function packInt64($number, $endianess = self::LITTLE_ENDIAN) {
        if (PHP_INT_SIZE < 8) {
            throw new Exception('64-bit functions not available on 32-bit installations.');
        }
        if (PHP_VERSION_ID < 50603) {
            return self::pack($number, 8, $endianess);
        }
        // pack had 64-bit functions added in 5.6.3,
        $s = pack('q', $number);
        if ((SYS_BIG_ENDIAN ? self::BIG_ENDIAN : self::LITTLE_ENDIAN) != $endianess) {
            return strrev($s);
        }

        return $s;
    }

    /**
     * Pack a signed 64-bit integer with a specific endianess.
     *
     * On 32-bit systems, this will only pack the lower 4 bytes, padding the output to 8
     *
     * @param     $number
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return string
     * @throws Exception Exception 32-bit systems do not support 64-bit encoding.
     */
    public static function packUint64($number, $endianess = self::LITTLE_ENDIAN) {
        if (PHP_INT_SIZE < 8) {
            throw new Exception('64-bit functions not available on 32-bit installations.');
        }
        if (PHP_VERSION_ID < 50603) {
            return self::pack($number, 8, $endianess);
        }
        // pack had 64-bit functions added in 5.6.3,
        $out = pack($endianess == self::LITTLE_ENDIAN ? 'J' : 'P', $number);

        return $out[1];
    }

    /**
     * Unpack a signed 64-bit integer with a specific endianess.
     *
     * WARNING: Do not use on 32-bit PHP installations.
     *
     * @param     $data
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return int
     * @throws Exception Exception 32-bit systems do not support 64-bit encoding.
     */
    public static function unpackInt64($data, $endianess = self::LITTLE_ENDIAN) {
        if (PHP_INT_SIZE < 8) {
            throw new Exception('64-bit functions not available on 32-bit installations.');
        }
        if (BinStringStatic::_strlen($data) < 8) {
            throw new Exception('Insufficient data, expected 8 bytes, got ' . BinStringStatic::_strlen($data));
        }
        $isTargetLE = $endianess === self::LITTLE_ENDIAN;

        // Need to emulate 64-bit...

        $n = 0;
        if ($isTargetLE) {
            for ($i = 0; $i < 8; $i++) {
                $n |= (ord($data[$i]) << ($i * 8));
            }
        } else {
            for ($i = 0; $i < 8; $i++) {
                $n |= (ord($data[7 - $i]) << ($i * 8));
            }
        }

        return $n;
    }

    /**
     * Unpack an unigned 64-bit integer with a specific endianess.
     *
     * @param     $data
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return int
     * @throws Exception Exception 32-bit systems do not support 64-bit encoding.
     */
    public static function unpackUint64($data, $endianess = self::LITTLE_ENDIAN) {
        if (PHP_INT_SIZE < 8) {
            throw new Exception('64-bit functions not available on 32-bit installations.');
        }
        $isTargetLE = $endianess === self::LITTLE_ENDIAN;

        if (PHP_VERSION_ID >= 50603) {
            // pack had 64-bit functions added in 5.6.3,
            $out = unpack($isTargetLE ? 'J' : 'P', $data);

            return $out[1];
        }

        return self::int2Uint64(self::unpackInt64($data, $endianess));
    }

    /**
     * @param int $number
     * @param int $bytes     number of bytes to pack. 8, 4, 2 or 1;
     * @param int $endianess DataHandler::LITTLE_ENDIAN or DataHandler::BIG_ENDIAN
     *
     * @return string
     */
    public static function pack($number, $bytes, $endianess) {
        return $endianess === self::LITTLE_ENDIAN ? self::packLE($number, $bytes) : self::packBE($number, $bytes);
    }

    /**
     * @param $number
     * @param $bytes number of bytes to pack. 8, 4, 2 or 1;
     *
     * @return string
     */
    public static function packLE($number, $bytes) {
        $se = "";
        $s = "";

        if ($bytes > 4 && PHP_INT_SIZE < 8) {
            $se = $number < 0 ? "\xff\xff\xff\xff" : "\x00\x00\x00\x00";
            $bytes = 4;
        }
        for ($i = 0; $i < $bytes; $i++) {
            $b = ($number >> ($i * 8)) & 0xff;
            $s .= pack("C", $b);
        }

        return $s . $se;
    }

    /**
     * @param $number
     * @param $bytes number of bytes to pack. 8, 4, 2 or 1;
     *
     * @return string
     */
    public static function packBE($number, $bytes) {
        $s = "";
        if ($bytes == 8 && PHP_INT_SIZE < 8) {
            $s = $number < 0 ? "\xff\xff\xff\xff" : "\x00\x00\x00\x00";
            $bytes = 4;
        }
        for ($i = $bytes - 1; $i >= 0; $i--) {
            $b = ($number >> ($i * 8)) & 0xff;
            $s .= pack("C", $b);
        }

        return $s;
    }

    /**
     * Convert an unsigned 16-bit integer to a signed 16-bit integer.
     *
     * @param $number
     *
     * @return int
     */
    public static function uint2Int16($number) {
        $number = $number & 0xffff;
        if ($number >= 0x8000) { // 1<<15
            $number -= 0x10000; // 1<<16;
        }

        return $number;
    }

    /**
     * Convert a signed 16-bit integer to an unsigned 16-bit integer.
     *
     * @param $number
     *
     * @return int
     */
    public static function int2Uint16($number) {
        $number = $number & 0xffff;
        if ($number < 0) {
            $number += 0x10000; // 1<<16;
        }

        return $number;
    }

    /**
     * Convert an unsigned 32-bit integer to a signed 32-bit integer.
     *
     * @param $number
     *
     * @return int
     */
    public static function uint2Int32($number) {
        if ($number >= 0x80000000) { // 1<<31
            // 1<<32 would have created a number beyond the 32 bit range. A potential problem on 32 bit systems.
            $number -= 0xffff0000;
            $number -= 0x0000ffff;
            $number--;
        }

        return $number;
    }

    /**
     * Convert a signed 32-bit integer to an unsigned 32-bit integer.
     *
     * @param $number
     *
     * @return int
     */
    public static function int2Uint32($number) {
        if ($number < 0) {
            // 1<<32 would have created a number beyond the 32 bit range. A potential problem on 32 bit systems.

            $number += 0xffff0000;
            $number += 0x0000ffff;
            $number++;
        }

        return $number;
    }

    /**
     * Convert an unsigned 64-bit integer to a signed 64-bit integer.
     *
     * @param $number
     *
     * @return int
     * @throws Exception Exception 32-bit systems do not support 64-bit encoding.
     */
    public static function uint2Int64($number) {
        if (PHP_INT_SIZE < 8) {
            throw new Exception('64-bit functions not available on 32-bit installations.');
        }
        if ($number < 0) { // 1<<63
            // 1<<64 would have created a number beyond the 64-bit range.
            $number -= 0xffffffffffffffff;
            $number--;
        }

        return $number;
    }

    /**
     * Convert a signed 64-bit integer to an unsigned 64-bit integer.
     *
     * @param $number
     *
     * @return int
     * @throws Exception 32-bit systems do not support 64-bit encoding.
     */
    public static function int2Uint64($number) {
        if (PHP_INT_SIZE < 8) {
            throw new Exception('64-bit functions not available on 32-bit installations.');
        }
        if ($number < 0) {
            // 1<<64 would have created a number beyond the 64-bit range.
            $number += 0xffffffffffffffff;
            $number++;
        }

        return $number;
    }

    public static function is64bitSupported() {
        return PHP_INT_SIZE >= 8;
    }

    public static function isSystemLittleEndian() {
        return pack('L', 1) !== pack('N', 1);
    }

    public static function isSystemBigEndian() {
        return pack('L', 1) === pack('N', 1);
    }
}
