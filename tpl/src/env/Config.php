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

	}

	public function apply(array $config) {
		foreach ($config as $name => $section) $this->addSection($name, $section);
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
