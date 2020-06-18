<?php
namespace JKingWeb\DrUUID;

class UUIDStorageVolatile implements UUIDStorage {
	protected $node = NULL;
	protected $timestamp = NULL;
	protected $sequence = NULL;

	public function getNode() {
		if ($this->node === NULL) 
			return;
		return $this->node;
	}

	public function getSequence($timestamp, $node) {
		if ($node != $this->node) {
			$this->node = $node;
			return;
		}
		if ($this->sequence === NULL) 
			return;
		if ($timestamp <= $this->timestamp)
			$this->sequence = pack("n", (unpack("nseq", $this->sequence)['seq'] + 1) & self::maxSequence);
		$this->setTimestamp($timestamp);
		return $this->sequence;
	}

	public function setSequence($sequence) {
		$this->sequence = pack("n", unpack("nseq", $sequence)['seq'] & self::maxSequence);
	}

	public function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
	}
}