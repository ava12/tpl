<?php

namespace ava12\tpl\env;

use \ava12\tpl\machine\Machine;
use \ava12\tpl\parser\Parser;
use \ava12\tpl\lib\FileSys;

class Env {
	/** @var Config */
	public $config;

	/** @var Machine */
	public $machine;

	/** @var Parser */
	public $parser;

	/** @var FileSys */
	public $fileSys;


	/**
	 * @param Config|array $config
	 */
	public function init($config = []) {
		if ($config) {
			if (!is_object($config)) $config = new Config($config);
			$this->config = $config;
		}
		if (!$this->machine) $this->machine = new Machine;
		if (!$this->parser) $this->parser = new Parser($this->machine);
		if (!$this->fileSys) FileSys::setup($this);
	}
}
