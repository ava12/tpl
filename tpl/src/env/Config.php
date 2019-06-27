<?php

namespace ava12\tpl\env;

class Config {

	/** @var FileConfig */
	public $file;
	/** @var LibConfig */
	public $lib;

	public function __construct(array $configs = []) {
		$this->file = new FileConfig;
		$this->lib = new LibConfig;
		if ($configs) {
			$this->apply($configs);
		}
	}

	public function apply(array $configs) {
		foreach ($configs as $name => $section) {
			$this->addSection($name, $section);
		}
	}

	public function addSection($name, $config) {
		switch ($name) {
			case 'file':
				$this->file->apply($config);
			break;

			case 'lib':
				$this->lib->apply($config);
			break;
		}
	}
}
