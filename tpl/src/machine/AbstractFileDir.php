<?php

namespace ava12\tpl\machine;

abstract class AbstractFileDir implements IListValue {
	// {имя_поля|имя_поля: исходное_имя|имя_метода: [исходное_имя, количество_параметров]}
	protected static $members = [];

	protected static $accessFlagNames = [
		FileSys::PERM_READ => 'read',
		FileSys::PERM_APPEND => 'append',
		FileSys::PERM_WRITE => 'write',
		FileSys::PERM_RENAME => 'rename',
		FileSys::PERM_DELETE => 'delete',
	];

	/** @var Machine */
	protected $machine;
	/** @var FileLib */
	protected $fileLib;

	protected $path;
	protected $realName;
	protected $perm;
	protected $error = 0;
	/** @var ListValue */
	protected $memberList;
	/** @var ListValue */
	protected $permList;


	public function __construct($machine, $fileLib, $pathInfo) {
		$this->machine = $machine;
		$this->fileLib = $fileLib;
		$this->realName = $pathInfo->realName;
		$this->perm = $pathInfo->perm;
		$this->error = $pathInfo->error;

		$path = explode(FileSys::DS, $pathInfo->name);
		if (count($path) > 1) {
			$this->name = array_pop($path);
			$this->path = implode(FileSys::DS, $path) . FileSys::DS;
		} else {
			$path = explode(FileSys::RS, $path[0]);
			$this->path = $path[0] . FileSys::RS;
			$this->name = (isset($path[1]) ? $path[1] : '');
		}
	}

	public function getType() { return IValue::TYPE_LIST; }
	public function copy() { return $this; }
	public function getRawValue() { return $this; }

	public function getByIndex($index) {
		return $this->memberList->getByIndex($index);
	}

	public function getByKey($key) {
		return $this->memberList->getByKey($key);
	}

	public function setByIndex($index, $var) {
		throw new RunException(RunException::SET_CONST);
	}

	public function setByKey($key, $var) {
		throw new RunException(RunException::SET_CONST);
	}

	public function isPublic() { return false; }

	public function getCount() {
		return $this->memberList->getCount();
	}

	public function getKeyByIndex($index) {
		return $this->memberList->getKeyByIndex($index);
	}

	public function getIndexByKey($key) {
		return $this->memberList->getIndexByKey($key);
	}

	public function getIterator($value, $key, $index) {
		return $this->memberList->getIterator($value, $key, $index);
	}

	public function concat($var) {
		throw new RunException(RunException::SET_CONST);
	}

	public function getKeys() {
		return $this->memberList->getKeys();
	}

	public function getValues() {
		return $this->memberList->getValues();
	}

	public function slice($start, $count) {
		return $this->memberList->slice($start, $count);
	}

	public function splice($start, $count, $insert) {
		return $this->memberList->splice($start, $count, $insert);
	}

	public function makeVar() {
		if (!$this->memberList) {
			$this->memberList = new ListValue;
			$this->makePermList();
			$this->addValueMembers();
		}
		return new Variable($this);
	}

	protected function addValueMembers() {
		$list = $this->memberList;
		foreach (static::$members as $name => $def) {
			if (is_numeric($name)) $name = $def;
			if (is_array($def)) {
				$callback = [$this, $def[0]];
				$item = new Closure(new FunctionProxy($callback, $def[1]), null);
			} elseif (is_object($this->$def)) {
				$item = $this->$def;
			} else {
				$item = new ScalarValue($this->$def);
			}

			$list->addItem(new Variable($item, true), $name);
		}
	}

	protected function makePermList() {
		$this->permList = new ListValue;
		foreach (static::$accessFlagNames as $flag => $name) {
			$flag = (bool)($this->perm & $flag);
			$this->permList->addItem(new Variable(new ScalarValue($flag), true), $name);
		}
	}
}
