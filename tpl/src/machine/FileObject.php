<?php

namespace ava12\tpl\machine;

use \ava12\tpl\Util;

class FileObject extends AbstractFileDir {
	protected static $members = [
		'name' => ['callName', 0],
		'path' => ['callPath', 0],
		'error' => ['callError', 0],
		'exists' => ['callExists', 0],
		'perm' => ['callPerm', 0],
		'load' => ['callLoad', 0],
		'save' => ['callSave', 0],
		'append' => ['callAppend', 0],
		'rename' => ['callRename', 1],
		'move' => ['callMove', 2],
		'delete' => ['callDelete', 0],
	];

	protected static $nlRe = '/\\r\\n?/';

	protected $name;


	protected function addValueMembers() {
		$this->memberList->addItem(new Variable(new ScalarValue('')), 'content');
		parent::addValueMembers();
	}

	public function setByIndex($index, $var) {
		if ($index < 1) $index += $this->memberList->getCount();
		if ($index == 1) $this->memberList->setByIndex(1, $var);
		else throw new RunException(RunException::SET_CONST);
	}

	public function setByKey($key, $var) {
		if ($key === 'content') $this->memberList->setByIndex(1, $var);
		else throw new RunException(RunException::SET_CONST);
	}

	public function concat($var) {
		$this->memberList->getByIndex(1)->getValue()->concat($var->getValue());
	}

	protected function can($perm) {
		return (bool)($perm & $this->perm);
	}

	public function callName() {
		return $this->name;
	}

	public function callPath() {
		return $this->path;
	}

	public function callError() {
		return $this->error;
	}

	public function callExists() {
		return is_file($this->realName);
	}

	public function callPerm() {
		return new Variable($this->permList);
	}

	public function callLoad() {
		if ($this->error) return null;

		if (file_exists($this->realName)) {
			$content = file_get_contents($this->realName);
			if (!Util::checkEncoding($content)) {
				$this->error = FileSys::ERR_ENCODING;
				return null;
			}

			$content = preg_replace(static::$nlRe, "\n", $content);
		} else {
			$content = '';
		}
		$this->memberList->setByIndex(1, new Variable(new ScalarValue($content)));

		return null;
	}

	protected function convertEols($content) {
		if (PHP_EOL <> "\n") $content = str_replace("\n", PHP_EOL, $content);
		return $content;
	}

	public function callSave() {
		if ($this->error) return null;

		if (!$this->can(FileSys::PERM_WRITE)) {
			$this->error = FileSys::ERR_PERM;
			return null;
		}

		$content = $this->memberList->getByIndex(1)->getValue()->getRawValue();
		$content = $this->convertEols($content);
		file_put_contents($this->realName, $content);
		return null;
	}

	public function callAppend() {
		if ($this->error) return null;

		if (!$this->can(FileSys::PERM_APPEND)) {
			$this->error = FileSys::ERR_PERM;
			return null;
		}

		$content = $this->memberList->getByIndex(1)->getValue()->getRawValue();
		$content = $this->convertEols($content);
		file_put_contents($this->realName, $content, FILE_APPEND);
		return null;
	}

	protected function renMov($newPath, $newName) {
		$tail = substr($newPath, -1, 1);
		if ($tail <> FileSys::DS and $tail <> FileSys::RS) {
			$newPath .= FileSys::DS;
		}

		if (!$this->fileLib->isValidFileName($newName)) {
			$this->error = FileSys::ERR_NAME;
			return;
		}

		$pathInfo = $this->fileLib->getFileInfo($newPath . $newName);
		if ($pathInfo->error) {
			$this->error = $pathInfo->error;
		} elseif ($pathInfo->exists) {
			$this->error = FileSys::ERR_EXISTS;
		} elseif (!($pathInfo->perm & FileSys::PERM_CREATE)) {
			$this->error = FileSys::ERR_PERM;
		} elseif (is_file($this->realName) and !@rename($this->realName, $pathInfo->realName)) {
			$this->error = FileSys::ERR_ERR;
		}

		if (!$this->error) {
			$this->path = $newPath;
			$this->name = $newName;
			$this->realName = $pathInfo->realName;
		}
	}

	public function callRename($args) {
		if ($this->error) return null;

		if (!$this->can(FileSys::PERM_RENAME)) {
			$this->error = FileSys::ERR_PERM;
			return null;
		}

		$newName = $this->machine->toString($args[0]);
		$this->renMov($this->path, $newName);
		return null;
	}

	public function callMove($args) {
		if ($this->error) return null;

		if (!$this->can(FileSys::PERM_RENAME) or !$this->can(FileSys::PERM_DELETE)) {
			$this->error = FileSys::ERR_PERM;
			return null;
		}

		$newPath = $this->machine->toString($args[0]);
		$newName = $this->machine->toString($args[1]);
		if (!strlen($newName)) $newName = $this->name;
		$this->renMov($newPath, $newName);
		if (!$this->error) {
			$this->perm = FileSys::PERM_ALL;
			$this->makePermList();
		}
		return null;
	}

	public function callDelete() {
		if ($this->error) return null;

		if (!$this->can(FileSys::PERM_DELETE)) {
			$this->error = FileSys::ERR_PERM;
			return null;
		}

		if (is_file($this->realName)) {
			if (@unlink($this->realName)) {

			} else {
				$this->error = FileSys::ERR_ERR;
			}
		}

		$this->perm = $this->fileLib->getFileInfo($this->path . $this->name)->perm;
		$this->makePermList();
		return null;
	}
}
