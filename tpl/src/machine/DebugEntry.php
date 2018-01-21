<?php

namespace ava12\tpl\machine;

class DebugEntry {
	public $functionName;
	public $sourceName;
	public $line;
	public $callerEntry;
	public $functionIndex;
	public $chunkIndex;
	public $chunkIp;

	public function __construct($functionName, $sourceName, $line, $callerEntry = null, $functionIndex = null, $chunkIndex = null, $chunkIp = null) {
		$this->functionName = $functionName;
		$this->sourceName = $sourceName;
		$this->line = $line;
		$this->callerEntry = $callerEntry;
		$this->functionIndex = $functionIndex;
		$this->chunkIndex = $chunkIndex;
		$this->chunkIp = $chunkIp;
	}
}
