<?php

namespace ava12\tpl\env;

class FileConfig {
	const FIELD_SEPARATOR = ';';

	public $roots = []; // [[имя, путь, права]*]
	public $nameEncoding = null;
	public $nameChars = '';

	public function __construct(array $config = []) {
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
					$this->addRoots($roots);
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
