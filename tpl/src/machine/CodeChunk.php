<?php

namespace ava12\tpl\machine;

class CodeChunk {
	const TYPE_MISC = 'misc';
	const TYPE_LOOP = 'loop';
	const TYPE_DO = 'do';
	const TYPE_CASE = 'case';


	public $index;
	public $type;
	public $code;
	public $debugInfo = []; // {ip: [sourceId, line]}
	protected $debugIp = null;


	public function __construct($index, $type = self::TYPE_MISC, $code = []) {
		$this->index = $index;
		$this->type = $type;
		$this->code = $code;
	}

	public function emit($op, $sourceId = -1, $line = -1) {
		$ip = count($this->code);
		$this->code[$ip] = $op;
		if (isset($this->debugIp)) {
			$entry = $this->debugInfo[$this->debugIp];
			if ($entry[0] == $sourceId and $entry[1] == $line) {
				return;
			}
		}

		$this->debugIp = $ip;
		$this->debugInfo[$ip] = [$sourceId, $line];
	}

	protected function findDebugEntryIp($ip) {
		$debugIps = array_keys($this->debugInfo);
		if (!$debugIps) return null;

		$left = 0;
		$right = count($debugIps) - 1;
		while ($left <= $right) {
			$i = ($left + $right) >> 1;
			$debugIp = $debugIps[$i];
			if ($debugIp == $ip) return $ip;

			if ($debugIp < $ip) $left = $i + 1;
			else $right = $i - 1;
		}

		return $debugIps[min($left, $right)];
	}

	public function findDebugEntry($ip) {
		$ip = $this->findDebugEntryIp($ip);
		if (isset($ip)) {
			$entry = $this->debugInfo[$ip];
			return ['sourceId' => $entry[0], 'line' => $entry[1]];
		}
		else return ['sourceId' => null, 'line' => null];
	}
}
