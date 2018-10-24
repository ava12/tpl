<?php

namespace ava12\tpl\machine;

class DebugEntry {
	public $functionName;
	public $sourceName;
	public $line;
	public $callerEntry;
	public $funcDef;
	public $chunkIndex;
	public $chunkIp;

	/**
	 * @param string $functionName
	 * @param string $sourceName
	 * @param int $line
	 * @param DebugEntry|null $callerEntry
	 * @param FunctionDef $funcDef
	 * @param int|null $chunkIndex
	 * @param int|null $chunkIp
	 */
	public function __construct($functionName, $sourceName, $line, $callerEntry, $funcDef, $chunkIndex = null, $chunkIp = null) {
		$this->functionName = $functionName;
		$this->sourceName = $sourceName;
		$this->line = $line;
		$this->callerEntry = $callerEntry;
		$this->funcDef = $funcDef;
		$this->chunkIndex = $chunkIndex;
		$this->chunkIp = $chunkIp;
	}
}
