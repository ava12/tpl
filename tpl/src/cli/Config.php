<?php

namespace ava12\tpl\cli;

use ava12\tpl\Util;

class Config {
	const WIN_CON_ENC = 'CP866';
	const CON_ENC_ENV = 'OS_CON_ENC';

	public $consoleEnc = null;
	public $inputMask = [];
	public $outputDir = null;
	public $outputSuffix = null;
	public $stdout = false;
	public $testMode = false;

	public function __construct() {
		if (Util::isWindows()) {
			$this->consoleEnc = self::WIN_CON_ENC;
		}
		$env = getenv(self::CON_ENC_ENV);
		if ($env) $this->consoleEnc = $env;
	}

	public function apply(array $config) {
		foreach ($config as $key => $value) {
			switch ($key) {
				case 'consoleEnc':
				case 'outputDir':
				case 'outputSuffix':
					if (!isset($this->$key)) {
						$this->$key = (string)$value;
					}
					break;

				case 'stdout':
				case 'testMode':
					$this->$key = ($this->$key or (bool)$value);
					break;

				case 'inputMask':
					$this->inputMask = array_merge((array)$value, $this->inputMask);
					break;
			}
		}
	}

	public function decodeCon($text) {
		return ($this->consoleEnc ? mb_convert_encoding($text, 'UTF-8', $this->consoleEnc): $text);
	}

	public function encodeCon($text) {
		return ($this->consoleEnc ? mb_convert_encoding($text, $this->consoleEnc, 'UTF-8'): $text);
	}
}
