<?php

namespace ava12\tpl\env;

class Config {

	/** @var FileConfig */
	public $file;
	/** @var LibConfig */
	public $lib;

	const FILE_SECTION = 'file';
	const LIB_SECTION = 'lib';

	public function __construct(array $configs = []) {
		$this->file = new FileConfig;
		$this->lib = new LibConfig;
		if ($configs) {
			$this->apply($configs);
		}
	}

	public function apply(array $configs) {
		$this->file->apply($this->getConfig($configs, self::FILE_SECTION));
		$this->lib->apply($this->getConfig($configs, self::LIB_SECTION));
	}

	protected function getConfig($configs, $section) {
		return (isset($configs[$section]) ? (array)$configs[$section] : []);
	}
}
