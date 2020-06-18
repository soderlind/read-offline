<?php
namespace JKingWeb\DrUUID;

interface UUIDStorage {
	public function getNode(); // return bytes or NULL if node cannot be retrieved
	public function getSequence($timestamp, $node); // return bytes or NULL if sequence is not available; this method should also update the stored timestamp
	public function setSequence($sequence);
	public function setTimestamp($timestamp);
	const maxSequence = 16383; // 00111111 11111111
}