<?php

namespace ava12\tpl\lib;

use \ava12\tpl\machine\Machine;
use \ava12\tpl\env\Env;


class FileLib implements ILib {
	protected static $funcs = [
		'file' => 'callFile',
		'dir' => 'callDir',
	];

	/** @var Machine */
	protected $machine;
	/** @var FileSys */
	protected $fileSys;

	/**
	 * @param Machine $machine
	 * @param FileSys $fileSys
	 */
	protected function __construct($machine, $fileSys) {
		$this->machine = $machine;
		$this->fileSys = $fileSys;
	}

	public static function setup(Env $env) {
		$machine = $env->machine;
		$fileSys = $env->fileSys;
		$instance = new static($machine, $fileSys);
		$mainFunc = $machine->getFunction();
		foreach (static::$funcs as $name => $func) {
			FunctionProxy::inject($mainFunc, $name, [$instance, $func], 1);
		}
		return $instance;
	}

	public function callFile($args) {
		$name = $this->machine->toString($args[0]);
		return $this->getFileObject($name)->makeVar();
	}

	public function callDir($args) {
		$name = $this->machine->toString($args[0]);
		return $this->getDirObject($name)->makeVar();
	}

	public function getFileObject($name) {
		$path = $this->getFileInfo($name);
		return new FileObject($this->machine, $this, $path);
	}

	public function getDirObject($name) {
		$path = $this->getDirInfo($name);
		return new DirObject($this->machine, $this, $path);
	}

	public function getFileInfo($name) {
		return $this->fileSys->getFileInfo($name);
	}

	public function getDirInfo($name) {
		return $this->fileSys->getDirInfo($name);
	}

	public function isValidFileName($name) {
		return $this->fileSys->isValidFileName($name);
	}

	public function isValidSearchMask($mask) {
		return $this->fileSys->isValidSearchMask($mask);
	}

	public function encodeName($name) {
		return $this->fileSys->encodeName($name);
	}

	public function decodeName($name) {
		return $this->fileSys->decodeName($name);
	}
}

