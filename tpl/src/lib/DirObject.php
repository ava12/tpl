<?php

namespace ava12\tpl\lib;

use \ava12\tpl\machine\ListValue;
use \ava12\tpl\machine\Variable;


class DirObject extends AbstractFileDir {
	protected static $members = [
		'path',
		'error',
		'perm' => 'permList',
		'parent' => ['callParent', 0],
		'dir' => ['callDir', 1],
		'file' => ['callFile', 1],
		'dirs' => ['callDirs', 2],
		'files' => ['callFiles', 2],
	];

	public function __construct($machine, $fileLib, $pathInfo) {
		parent::__construct($machine, $fileLib, $pathInfo);
		if (strlen($this->name)) {
			$this->path .= $this->name . FileSys::DS;
		}
	}

	public function callParent() {
		if ($this->error) return null;

		$path = FileSys::parentPath($this->path);
		return $this->fileLib->getDirObject($path)->makeVar();
	}

	public function callDir($args) {
		$name = $this->machine->toString($args[0]);
		$result = $this->fileLib->getDirObject($this->path . $name);
		return $result->makeVar();
	}

	public function callFile($args) {
		$name = $this->machine->toString($args[0]);
		$result = $this->fileLib->getFileObject($this->path . $name);
		return $result->makeVar();
	}

	protected function find($mask, $isDir) {
		$result = [];
		if (!strlen($mask)) $mask = '*';
		if (!$this->fileLib->isValidSearchMask($mask)) return $result;

		$path = $this->path;
		$flags = ($isDir ? GLOB_ONLYDIR : 0);
		$found = glob($this->realName . FileSys::DS . $mask, $flags);
		sort($found);

		foreach ($found as $name) {
			$name = $this->fileLib->decodeName($name);
			$name = FileSys::baseName($name);
			if ($this->fileLib->isValidFileName($name)) {
				$result[] = $path . $name;
			}
		}

		return $result;
	}

	public function callDirs($args) {
		$mask = $this->machine->toString($args[0]);
		$names = $this->find($mask, true);
		$result = new ListValue;
		foreach ($names as $name) {
			$dir = $this->fileLib->getDirObject($name);
			if (!$dir->error) $result->addItem($dir->makeVar(), FileSys::baseName($name));
		}
		return new Variable($result);
	}

	public function callFiles($args) {
		$mask = $this->machine->toString($args[0]);
		$names = $this->find($mask, false);
		$result = new ListValue;
		foreach ($names as $name) {
			$file = $this->fileLib->getFileObject($name);
			if (!$file->error) $result->addItem($file->makeVar(), FileSys::baseName($name));
		}
		return new Variable($result);
	}
}
