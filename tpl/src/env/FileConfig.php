<?php

namespace ava12\tpl\env;

use ava12\tpl\Util;

class FileConfig {
	const FIELD_SEPARATOR = ';';
	const WIN_FS_ENC = 'Windows-1251';
	const FS_ENC_ENV = 'OS_FS_ENC';

	public $roots = []; // [[имя, путь, права]*]
	public $nameEncoding = null;
	public $nameChars = '';

	public function __construct(array $config = []) {
		if (Util::isWindows()) {
			$this->nameEncoding = self::WIN_FS_ENC;
		}
		$env = getenv(self::FS_ENC_ENV);
		if ($env) $this->nameEncoding = $env;

		if ($config) $this->apply($config);
	}

	public function apply(array $config) {
		foreach ($config as $name => $value) {
			switch ($name) {
				case 'nameEncoding':
				case 'nameChars':
					$this->$name = (string)$value;
				break;

				case 'roots':
					$this->addRoots($value);
				break;
			}
		}
	}

	public function addRoots($roots) {
		foreach ((array)$roots as $root) {
			if (!is_array($root)) {
				$root = explode(self::FIELD_SEPARATOR, $root);
			}
			$this->roots[] = $root;
		}
	}

	public function addRoot($name, $path, $perm) {
		$this->roots[] = [$name, $path, $perm];
	}
}

