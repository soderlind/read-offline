<?php
namespace JKingWeb\DrUUID;

class UUIDStorageStable extends UUIDStorageVolatile {
	protected $file = NULL;
	protected $read = FALSE;
	protected $wrote = TRUE;
	protected static $storeExceptionClass = "\\JKingWeb\\DrUUID\\UUIDStorageException";

	public function __construct($path) {
		if (!file_exists($path)) {
			$dir = dirname($path);
			if (!is_writable($dir)) 
				throw new static::$storeExceptionClass("Stable storage is not writable.", 1102);
			if (!is_readable($dir)) 
				throw new static::$storeExceptionClass("Stable storage is not readable.", 1101);
		}
		else if (!is_writable($path)) 
			throw new static::$storeExceptionClass("Stable storage is not writable.", 1102);
		else if (!is_readable($path)) 
			throw new static::$storeExceptionClass("Stable storage is not readable.", 1101);
		$this->file = $path;
	}

	protected function readState() {
		if (!file_exists($this->file)) // a missing file is not an error
			return;
		$data = @file_get_contents($this->file);
		if ($data === FALSE) throw new static::$storeExceptionClass("Stable storage could not be read.", 1201);
		$this->read = TRUE;
		$this->wrote = FALSE;
		if (!$data) // an empty file is not an error
			return;
		$data = @unserialize($data);
		if (!is_array($data) || sizeof($data) < 3)
			throw new static::$storeExceptionClass("Stable storage data is invalid or corrupted.", 1203);
		list($this->node, $this->sequence, $this->timestamp) = $data;
	}
	
	public function getNode() {
		$this->readState();
		return parent::getNode();
	}

	public function setSequence($sequence) {
		if (!$this->read) {
			$this->readState();
		} 
		parent::setSequence($sequence);
		$this->write();
	}

	public function setTimestamp($timestamp) {
		parent::setTimestamp($timestamp);
		if ($this->wrote)
			return;
		$this->write();
	}
	
	protected function write($check = 1) {
		$data = serialize(array($this->node, $this->sequence, $this->timestamp));
		$write = @file_put_contents($this->file,$data);
		if ($check)	
			if ($write === FALSE) throw new static::$storeExceptionClass("Stable storage could not be written.", 1202);
		$this->wrote = TRUE;
		$this->read = FALSE;
	}
	
	public function __destruct() {
		$this->write(0);
	}
}