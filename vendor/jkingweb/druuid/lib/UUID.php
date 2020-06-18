<?php
namespace JKingWeb\DrUUID;

class UUID {
	const MD5  = 3;
	const SHA1 = 5;
	const clearVer = 15;  // 00001111  Clears all bits of version byte with AND
	const clearVar = 63;  // 00111111  Clears all relevant bits of variant byte with AND
	const varRes   = 224; // 11100000  Variant reserved for future use
	const varMS    = 192; // 11000000  Microsft GUID variant
	const varRFC   = 128; // 10000000  The RFC 4122 variant (this variant)
	const varNCS   = 0;   // 00000000  The NCS compatibility variant
	const version1 = 16;  // 00010000
	const version3 = 48;  // 00110000
	const version4 = 64;  // 01000000
	const version5 = 80;  // 01010000
	const interval = "122192928000000000"; //  Time (in 100ns steps) between the start of the Gregorian and Unix epochs
	const nsDNS  = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
	const nsURL  = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
	const nsOID  = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
	const nsX500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';
	const bigChoose = -1;
	const bigNot    = 0;
	const bigNative = 1;
	const bigGMP    = 2;
	const bigBC     = 3;
	const bigSecLib = 4;
	const randChoose  = -1;
	const randPoor    = 0;
	const randDev     = 1;
	const randCAPICOM = 2;
	const randOpenSSL = 3;
	const randMcrypt  = 4;
	const randNative  = 5;
	//static properties
	protected static $randomFunc          = self::randChoose;
	protected static $randomSource        = NULL;
	protected static $bignum              = self::bigChoose;
	protected static $storeClass          = "\\JKingWeb\\DrUUID\\UUIDStorageStable";
	protected static $storeClassVolatile  = "\\JKingWeb\\DrUUID\\UUIDStorageVolatile";
	protected static $storeExceptionClass = "\\JKingWeb\\DrUUID\\UUIDStorageException";
	protected static $exceptionClass      = "\\JKingWeb\\DrUUID\\UUIDException";
	protected static $store               = NULL;
	protected static $secLib              = NULL;
	//instance properties
	protected $bytes;
	protected $hex;
	protected $string;
	protected $urn;
	protected $version;
	protected $variant;
	protected $node;
	protected $time;
	
	public static function mint($ver = 1, $node = NULL, $ns = NULL, $time = NULL) {
		/* Create a new UUID based on provided data. */
		switch((int) $ver) {
			case 1:
				return new static(self::mintTime($node, $ns, $time));
			case 2:
				// Version 2 is not supported 
				throw new static::$exceptionClass("Version 2 is unsupported.",2);
			case 3:
				return new static(self::mintName(self::MD5, $node, $ns));
			case 4:
				return new static(self::mintRand());
			case 5:
				return new static(self::mintName(self::SHA1, $node, $ns));
			default:
				throw new static::$exceptionClass("Selected version is invalid or unsupported.",1);
		}
	}

	public static function mintStr($ver = 1, $node = NULL, $ns = NULL, $time = NULL) {
		/* If a randomness source hasn't been chosen, use the lowest common denominator. */
		if (self::$randomFunc == self::randChoose) self::$randomFunc = self::randPoor;
		/* Create a new UUID based on provided data and output a string rather than an object. */
		switch((int) $ver) {
			case 1:
				$uuid = self::mintTime($node, $ns, $time);
				break;
			case 2:
				// Version 2 is not supported 
				throw new static::$exceptionClass("Version 2 is unsupported.",2);
				break;
			case 3:
				$uuid = self::mintName(self::MD5, $node, $ns);
				break;
			case 4:
				$uuid = self::mintRand();
				break;
			case 5:
				$uuid = self::mintName(self::SHA1, $node, $ns);
				break;
			default:
				throw new static::$exceptionClass("Selected version is invalid or unsupported.",1);
		}
		return 
			bin2hex(substr($uuid,0,4))."-".
			bin2hex(substr($uuid,4,2))."-".
			bin2hex(substr($uuid,6,2))."-".
			bin2hex(substr($uuid,8,2))."-".
			bin2hex(substr($uuid,10,6));
	}

	public static function import($uuid) {
		/* Import an existing UUID. */
		return ($uuid instanceof self) ? $uuid : new static(self::makeBin($uuid));
	}   

	public static function compare($a, $b) {
		/* Compares the binary representations of two UUIDs.
		   The comparison will return true if they are bit-exact,
		   or if neither is valid. */
		if (self::makeBin($a)==self::makeBin($b))
			return TRUE;
		else
			return FALSE;
	}
	
	public static function seq() {
		/* Generate a random clock sequence; this is just two random bytes with the two most significant bits set to zero. */
		$seq = self::randomBytes(2);
		$seq[0] = chr(ord($seq[0]) & self::clearVar);
		return $seq;
	}

	public function __toString() {
		return $this->string;
	}

	public function __get($var) {
		switch($var) {
			case "bytes":
				return $this->bytes;
			case "hex":
				return bin2hex($this->bytes);
			case "string":
				return $this->string;
			case "urn":
				return "urn:uuid:".$this->string;
			case "version":
				return ord($this->bytes[6]) >> 4;
			case "variant":
				$byte = ord($this->bytes[8]);
				if ($byte >= self::varRes)
					return 3;
				if ($byte >= self::varMS)
					return 2;
				if ($byte >= self::varRFC)
					return 1;
				else
					return 0;
			case "node":
				if (ord($this->bytes[6])>>4==1)
					return bin2hex(strrev(substr($this->bytes,10)));
				else
					return NULL; 
			case "time":
				if (ord($this->bytes[6])>>4==1) {
					// Restore contiguous big-endian byte order
					$time = bin2hex($this->bytes[6].$this->bytes[7].$this->bytes[4].$this->bytes[5].$this->bytes[0].$this->bytes[1].$this->bytes[2].$this->bytes[3]);
					// Clear version flag
					$time[0] = "0"; 
					// Decode the hex digits and return a fixed-precision string
					$time = self::decodeTimestamp($time);
					return $time;
				}
				else
					return NULL;
			default:
				return NULL;
		}
	}

	protected function __construct($uuid) {
		if (strlen($uuid) != 16)
			throw new static::$exceptionClass("Input must be a valid UUID.",3);
		$this->bytes  = $uuid;
		// Optimize the most common use
		$this->string = 
			bin2hex(substr($uuid,0,4))."-".
			bin2hex(substr($uuid,4,2))."-".
			bin2hex(substr($uuid,6,2))."-".
			bin2hex(substr($uuid,8,2))."-".
			bin2hex(substr($uuid,10,6));
	}

	protected static function mintTime($node = NULL, $seq = NULL, $time = NULL) {
		/* Generates a Version 1 UUID.  
		   These are derived from the time at which they were generated. */
		// Check for native 64-bit integer support
		if (self::$bignum == self::bigChoose)
			self::$bignum = (PHP_INT_SIZE >= 8) ? self::bigNative : self::bigNot;
		// ensure a store is available
		if (self::$store === NULL) 
			self::$store = new static::$storeClassVolatile;
		// check any input for correctness and communicate with the store where appropriate
		list($node, $seq, $time) = self::checkTimeInput($node, $seq, $time);
		// construct a 60-bit timestamp, padded to 64 bits
		$time = self::buildTime($time);
		// Reorder bytes to their proper locations in the UUID
		$uuid  = $time[4].$time[5].$time[6].$time[7].$time[2].$time[3].$time[0].$time[1];
		// Add the clock sequence
		$uuid .= $seq;
		// set variant
		$uuid[8] = chr(ord($uuid[8]) & self::clearVar | self::varRFC);
		// set version
		$uuid[6] = chr(ord($uuid[6]) & self::clearVer | self::version1);
		// Set the final 'node' parameter, a MAC address
		$uuid .= $node;
		return $uuid;
	}

	protected static function mintRand() {
		/* Generate a Version 4 UUID.  
		   These are derived solely from random numbers. */
		// generate random fields
		$uuid = self::randomBytes(16);
		// set variant
		$uuid[8] = chr(ord($uuid[8]) & self::clearVar | self::varRFC);
		// set version
		$uuid[6] = chr(ord($uuid[6]) & self::clearVer | self::version4);
		return $uuid;
	}

	protected static function mintName($ver, $node, $ns) {
		/* Generates a Version 3 or Version 5 UUID.
					These are derived from a hash of a name and its namespace, in binary form. */
		if ($ver == 3)
		if (!$node)
			throw new static::$exceptionClass("A name-string is required for Version 3 or 5 UUIDs.",201);
		// if the namespace UUID isn't binary, make it so
		$ns = self::makeBin($ns);
		if (!$ns)
			throw new static::$exceptionClass("A valid UUID namespace is required for Version 3 or 5 UUIDs.",202);
		switch($ver) {
			case self::MD5: 
				$version = self::version3;
				$uuid = md5($ns.$node,1);
				break;
			case self::SHA1:
				$version = self::version5;
				$uuid = substr(sha1($ns.$node,1),0, 16);
				break;
		}
		// set variant
		$uuid[8] = chr(ord($uuid[8]) & self::clearVar | self::varRFC);
		// set version
		$uuid[6] = chr(ord($uuid[6]) & self::clearVer | $version);
		return ($uuid);
	}

	protected static function CheckTimeInput($node, $seq, $time) {
		/* If no timestamp has been specified, generate one.
		   Note that this will never be more accurate than to 
		   the microsecond, whereas UUID timestamps are measured in 100ns steps. */
		$time = ($time !== NULL) ? self::normalizeTime($time) : self::normalizeTime(microtime(),1);
		/* If a node ID is supplied, use it and keep it in the store; if none is 
		   supplied, get it from the store or generate it if none is stored. */
		if ($node === NULL) {
			$node = self::$store->getNode();
			if (!$node) {
				$node = self::randomBytes(6);
				$node[0] = pack("C", ord($node[0]) | 1);
			}
		} else {
			$node = self::makeNode($node);
			if (!$node)
				throw new static::$exceptionClass("Node must be a valid MAC address.", 101);
		}
		// Do a sanity check on clock sequence if one is provided
		if ($seq !== NULL && strlen($seq) != 2)
			throw UUIDException("Clock sequence must be a two-byte binary string.",102);
		// If one is not provided, check stable/volatile storage for a valid clock sequence
		if ($seq === NULL)
			$seq = self::$store->getSequence($time, $node);
		// Generate a random clock sequence if one is not available
		if (!$seq) {
			$seq = self::seq();
			self::$store->setSequence($seq);
		}
		self::$store->setTimestamp($time);
		return array($node, $seq, $time);
	}

	protected static function normalizeTime($time, $expected = FALSE) {
		/* Returns a string representation of the 
		   number of 100ns steps since the Unix epoch. */
		if(is_a($time, "DateTimeInterface") || is_a($time, "DateTime"))
			return $time->format("U").str_pad($time->format("u"), 7, "0", STR_PAD_RIGHT);
		switch(gettype($time)) {
			case "string":
				$time = explode(" ", $time);
				if(sizeof($time) != 2) throw new static::$exceptionClass("Time input was of an unexpected format.",103);
				return $time[1].substr(str_pad($time[0], 9, "0", STR_PAD_RIGHT),2,7);
			case "integer": // assume a second-precision timestamp
				return $time."0000000";
			case "double":
				$time = sprintf("%F", $time);
				$time = explode(".", $time);
				return $time[0].substr(str_pad($time[1], 7, "0", STR_PAD_RIGHT),0,7);
			default:
				throw new static::$exceptionClass("Time input was of an unexpected format.",103);
		}
	}    

	protected static function buildTime($time) {
		switch (self::$bignum) {
			case self::bigNative:
				$out = base_convert($time + self::interval, 10, 16);
				break;
			case self::bigNot:
				// add the magic interval
				$out = $time + self::interval;
				// convert to a string, printing all digits rather than using scientific notation
				$out = sprintf("%F", $out);
				// strip decimal point if cast to float
				preg_match("/^\d+/", $out, $out);
				// convert to hexdecimal notation, big-endian
				$out = base_convert($out[0], 10, 16);
				break;
			case self::bigGMP:
				$out = gmp_strval(gmp_add($time, self::interval), 16);
				break;
			case self::bigBC:
				$out = bcadd($time, self::interval, 0);
				$in = $out; $out = "";
				/* BC Math does not have a native equivalent of base_convert(), 
				   so we have to fake it.  Chunking the number to as many 
				   nybbles as PHP can handle in an integer speeds things up lots. */
				$base = (int) hexdec(str_repeat("f", (PHP_INT_SIZE * 2) -1)) + 1;
				do {
					$mod = (int) bcmod($in,$base);
					$in = bcdiv($in,$base,0);
					$out = base_convert($mod, 10, 16).$out;
				} while($in > 0);
				break;
			case self::bigSecLib:
				$out = new self::$secLib($time);
				$out = $out->add(new self::$secLib(self::interval));
				$out = $out->toHex();
				break;
			default:
				throw new static::$exceptionClass("Bignum method not implemented.",901);
		}
		// convert to binary, padding to 8 bytes
		return pack("H*", str_pad($out, 16, "0", STR_PAD_LEFT));
	}  

	protected static function decodeTimestamp($hex) {
		/* Convrt a UUID timestamp (in hex notation) to 
		   a Unix timestamp with microseconds. */
		// Check for native 64-bit integer support
		if (self::$bignum == self::bigChoose)
			self::$bignum = (PHP_INT_SIZE >= 8) ? self::bigNative : self::bigNot;
		switch(self::$bignum) {
			case self::bigNative:
				$time = hexdec($hex) - self::interval; 
				break;
			case self::bigGMP:
				$time = gmp_strval(gmp_sub("0x".$hex, self::interval)); 
				break;
			case self::bigBC:
				/* BC Math does not natively handle hexadecimal input, 
				   so we must convert to decimal in safe-sized chunks. */
				$time = 0;
				$mul = 1;
				$size = PHP_INT_SIZE * 2 - 1;
				$max = hexdec(str_repeat("f", $size))+1;
				$hex = str_split(str_pad($hex, ceil(strlen($hex) / $size) * $size, 0, STR_PAD_LEFT), $size);
				do {
					$chunk = hexdec(array_pop($hex));
					$time = bcadd($time, bcmul($chunk, $mul));
					$mul = bcmul($max, $mul);
				} while (sizeof($hex));
				// And finally subtract the magic number to get the correct timestamp
				$time = bcsub($time, self::interval);
				break;
			case self::bigSecLib:
				$time = new self::$secLib($hex, 16);
				$time = $time->subtract(new self::$secLib(self::interval));
				$time = $time->toString();
				break;
			case self::bigNot:
				$time = sprintf("%F", hexdec($hex) - self::interval);
				preg_match("/^\d+/", $time, $time);
				$time = $time[0];
				break;
			default:
				throw new static::$exceptionClass("Bignum method not implemented.",901);
		}
		return substr($time,0,strlen($time)-7).".".substr($time,strlen($time)-7);
	}

	protected static function makeBin($str) {
		/* Ensure that an input string is a UUID.
		   Returns binary representation, or false on failure. */
		$len = 16;
		if ($str instanceof self)
			return $str->bytes;
		if (strlen($str)==$len)
			return $str;
		else
			$str = preg_replace("/^urn:uuid:/is", "", $str); // strip URN scheme and namespace
			$str = preg_replace("/[^a-f0-9]/is", "", $str);  // strip non-hex characters
			if (strlen($str) != ($len * 2))
				return FALSE;
			else
				return pack("H*", $str);
	}

	protected static function makeNode($str) {
		/* Parse a string to see if it's a MAC address.
		   If it's six bytes, don't touch it; if it's hex, reverse bytes */
		$len = 6;
		if (strlen($str)==$len)
			return $str;
		else
			$str = preg_replace("/[^a-f0-9]/is", "", $str);  // strip non-hex characters
			if (strlen($str) != ($len * 2))
				return FALSE;
			else // MAC addresses are little-endian and UUIDs are big-endian, so we reverse bytes
				return strrev(pack("H*", $str));
	}

	public static function randomBytes($bytes) {
		switch (self::$randomFunc) {
			case self::randChoose:
			case self::randPoor:
				/* Get the specified number of random bytes, using mt_rand(). */
				$rand = "";
				for ($a = 0; $a < $bytes; $a++) {
					$rand .= chr(mt_rand(0, 255));
				} 
				return $rand;
			case self::randNative:
				/* Get the specified number of bytes from the PHP core. 
				   This is available since PHP 7. */
				return random_bytes($bytes);
			case self::randDev:
				/* Get the specified number of random bytes using a file handle 
				   previously opened with UUID::initRandom(). */
				return fread(self::$randomSource, $bytes);
			case self::randOpenSSL:
				/* Get the specified number of bytes from OpenSSL.
				   This is available since PHP 5.3. */
				return openssl_random_pseudo_bytes($bytes);
			case self::randMcrypt:
				/* Get the specified number of random bytes via Mcrypt. */
				return mcrypt_create_iv($bytes);
			case self::randCOM:
				/* Get the specified number of random bytes using Windows'
				   randomness source via a COM object previously created by UUID::initRandom().
				   Straight binary mysteriously doesn't work, hence the base64. */
				return base64_decode(self::$randomSource->GetRandom($bytes,0));
			default:
				throw new static::$exceptionClass("Randomness source not implemented.",902);
		}
	} 

	public static function initAccurate() {
		$big = self::initBignum();
		if ($big == self::bigNot)
			throw new static::$exceptionClass("64-bit integer arithmetic is not available.",2001);
		$rand = self::initRandom();
		if ($rand == self::randPoor)
			throw new static::$exceptionClass("Secure random number generator is not available.",2002);
		if (!is_object(self::$store)) {
			try {
				call_user_func_array(array("self","initStorage"),func_gets_args());
			} catch(\Exception $e) {
				throw new static::$storeExceptionClass("Stable storage not available.", 2003, $e);
			}
		} else if (!(self::$store instanceof UUIDStorage)) {
			throw new static::$storeExceptionClass("Storage is invalid.", 2004);
		}
	}

	public static function initRandom($how = NULL) {
		/* Look for a system-provided source of randomness, which is usually crytographically secure.
		   /dev/urandom is tried first because tests suggest it is faster than other options. */
		if ($how === NULL) {
			if (self::$randomFunc != self::randChoose)
				return self::$randomFunc;
			else if (function_exists('random_bytes'))
				$how = self::randNative;
			else if (function_exists('openssl_random_pseudo_bytes'))
				$how = self::randOpenSSL;
			else if (function_exists('mcrypt_create_iv'))
				$how = self::randMcrypt;
			else if (is_readable('/dev/urandom')) 
				$how = self::randDev;
			else 
				$how = self::randCAPICOM;
			try {
				self::initRandom($how);
			} catch(\Exception $e) {
				self::$randomFunc = self::randPoor;
			}
		} else {
			$source = NULL;
			switch($how) {
				case self::randChoose:
					self::$randomFunc = $how;
					return self::initRandom();
				case self::randPoor:
					self::$randomFunc = $how;
					break;
				case self::randNative:
					if (!function_exists('random_bytes'))
						throw new static::$exceptionClass("Randomness source is not available.", 802);
					break;
			case self::randDev:
					$source = @fopen('/dev/urandom', 'rb');
					if (!$source) 
						throw new static::$exceptionClass("Randomness source is not available.", 802);
					break;
				case self::randOpenSSL:
					if (!function_exists('openssl_random_pseudo_bytes'))
						throw new static::$exceptionClass("Randomness source is not available.", 802);
					break;
				case self::randMcrypt:
					if (!function_exists('mcrypt_create_iv'))
						throw new static::$exceptionClass("Randomness source is not available.", 802);
					break;
				case self::randCAPICOM: // Only available in Windows XP and earlier. See http://msdn.microsoft.com/en-us/library/aa388182(VS.85).aspx
					if (!class_exists('COM', 0))
						throw new static::$exceptionClass("Randomness source is not available.", 802);
					try {$source = new \COM('CAPICOM.Utilities.1');}
					catch(\Exception $e) {throw new static::$exceptionClass("Randomness source is not available.", 802, $e);}
					break;
				default:
					throw new static::$exceptionClass("Randomness source not implemented.",902);
			}
			self::$randomSource = $source;
			self::$randomFunc = $how;
		}
		return self::$randomFunc;
	}

	public static function initBignum($how = NULL) {
		/* Check to see if PHP is running in a 32-bit environment and if so, 
		   use GMP or BC Math if available. */
		if ($how === NULL) {
			if (self::$bignum != self::bigChoose) { // determination has already been made
				return self::$bignum;
			} else if (PHP_INT_SIZE >= 8) {
				self::$bignum = self::bigNative;
			} else if (function_exists("gmp_add")) {
				self::$bignum = self::bigGMP;
			} else if (function_exists("bcadd")) {
				self::$bignum = self::bigBC;
			} else if (@class_exists("phpseclib\\Math\\BigInteger")) { // phpseclib v2.x
				self::$bignum = self::bigSecLib;
				self::$secLib = "\\phpseclib\\Math\\BigInteger";
			} else if (@class_exists("Math_BigInteger")) { // phpseclib v1.x
				self::$bignum = self::bigSecLib;
				self::$secLib = "\\Math_BigInteger";
			} else {
				self::$bignum = self::bigNot;
			} 
		} else {
			switch($how) {
				case self::bigChoose:
					self::$bignum = $how;
					return self::initBignum();
				case self::bigNot:
					break;
				case self::bigNative:
					if (PHP_INT_SIZE < 8) 
						throw new static::$exceptionClass("Bignum method is not available.", 801);
					break;
				case self::bigGMP:
					if (!function_exists("gmp_add"))
						throw new static::$exceptionClass("Bignum method is not available.", 801);
					break;
				case self::bigBC:
					if (!function_exists("bcadd"))
						throw new static::$exceptionClass("Bignum method is not available.", 801);
					break;
				case self::bigSecLib:
					if (class_exists("phpseclib\\Math\\BigInteger", 0)) //v2.x
						self::$secLib = "\\phpseclib\Math\\BigInteger";
					else if (class_exists("Math_BigInteger", 0)) //v1.x
						self::$secLib = "\\Math_BigInteger";
					else
						throw new static::$exceptionClass("Bignum method is not available.", 801);
					break;
				default:
					throw new static::$exceptionClass("Bignum method not implemented.", 901);
			}
			self::$bignum = $how;
		}
		return self::$bignum;
	}

	public static function initStorage($file = NULL) {
		if (static::$storeClass == "\\JKingWeb\\DrUUID\\UUIDStorageStable") {
			try {self::$store = new UUIDStorageStable($file);}
			catch(\Exception $e) {throw new static::$storeExceptionClass("Storage class could not be instantiated with supplied arguments.", 1003, $e);}
			return;
		} else if (static::$storeClass == "UUIDStorageStable") {
			try {self::$store = new \UUIDStorageStable($file);}
			catch(\Exception $e) {throw new static::$storeExceptionClass("Storage class could not be instantiated with supplied arguments.", 1003, $e);}
			return;
		}
		$store = new \ReflectionClass(static::$storeClass);
		$args = func_get_args();
		try {self::$store = $store->newInstanceArgs($args);} 
		catch(\Exception $e) {throw new static::$storeExceptionClass("Storage class could not be instantiated with supplied arguments.", 1003, $e);}
	}

	public static function registerStorage($name) {
		try {
			$store = new \ReflectionClass($name);
		} catch(\Exception $e) {
			throw new static::$storeExceptionClass("Storage class does not exist.", 1001, $e);
		}
		if (array_search("JKingWeb\\DrUUID\\UUIDStorage", $store->getInterfaceNames()) === FALSE)
			throw new static::$storeExceptionClass("Storage class does not implement the UUIDStorage interface.", 1002);
		static::$storeClass = $name;
		if (func_num_args() > 1) {
			$args = func_get_args();
			array_shift($args);
			try {
				self::$store = $store->newInstanceArgs($args);
			} catch(\Exception $e) {
				throw new static::$storeExceptionClass("Storage class could not be instantiated with supplied arguments.", 1003, $e);
			}
		}
	}
}