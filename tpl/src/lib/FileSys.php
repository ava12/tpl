<?php

namespace ava12\tpl\lib;

class FileSys {
	const PERM_READ = 1; // чтение файла
	const PERM_CREATE = 2; // создание файла и любые операции с созданным
	const PERM_APPEND = 4; // добавление данных к чужим файлам
	const PERM_WRITE = 8; // запись чужих файлов
	const PERM_RENAME = 16; // переименование чужих файлов
	const PERM_DELETE = 32; // удаление чужих файлов
	const PERM_INCLUDE = 64; // включение и парсинг скриптов TPL

	const PERM_ALL = 63; // кроме PERM_INCLUDE

	const ERR_ERR = 1; // системная ошибка
	const ERR_ROOT = 2; // неизвестный корень
	const ERR_PATH = 3; // несуществующий каталог
	const ERR_NAME = 4; // некорректное имя
	const ERR_TYPE = 5; // некорректный тип (файл вместо каталога и наоборот, спецфайл)
	const ERR_PERM = 6; // запрещенная операция
	const ERR_EXISTS = 7; // файл уже существует (при переименовании/перемещении)
	const ERR_ENCODING = 8; // кодировка содержимого несовместима с UTF-8

	const RS = ':';
	const DS = '/';

	protected static $defaultFirstNameChars = 'A-Za-z0-9_!"#$%&\'()+,<=>@^_`|';
	protected static $defaultNameChars = 'A-Za-z0-9_.\\- !"#$%&\'()+,<=>@^_`|~';
	protected static $defaultLastNameChars = 'A-Za-z0-9_!"#$%&\'()+,<=>@^_`|';
	protected static $wildCards = '*?';
	protected static $dirSeparatorRe = '[\\/\\\\]';
	protected static $rootSeparatorRe = ':';
	protected static $rootDirSeparatorRe = '[:\\/\\\\]';

	protected static $charPerms = [
		'r' => self::PERM_READ,
		'c' => self::PERM_CREATE,
		'a' => self::PERM_APPEND,
		'w' => self::PERM_WRITE,
		'n' => self::PERM_RENAME,
		'd' => self::PERM_DELETE,
		'i' => self::PERM_INCLUDE,
		'*' => self::PERM_ALL,
	];

	protected $nameEncoding;
	protected $roots = []; // {name: [path, perm]}
	protected $defaultRoot;
	protected $extraNameChars = '';
	protected $fileRe;
	protected $fileNameRe;
	protected $searchNameRe;
	protected $rootNameRe;


	public function __construct($nameEncoding = null) {
		$this->nameEncoding = $nameEncoding;
		$this->makeRes();
	}

	public static function setup(\ava12\tpl\env\Env $env) {
		$config = $env->config->file;
		$instance = new static($config->nameEncoding);
		$instance->addNameChars($config->nameChars);
		foreach ($config->roots as $root) {
			$instance->addRoot($root[0], $root[1], $root[2]);
		}
		$env->fileSys = $instance;
	}

	public function encodeName($name) {
		if (isset($this->nameEncoding)) {
			$name = mb_convert_encoding($name, $this->nameEncoding);
		}
		return $name;
	}

	public function decodeName($name) {
		if (isset($this->nameEncoding)) {
			$name = mb_convert_encoding($name, mb_internal_encoding(), $this->nameEncoding);
		}
		return $name;
	}

	public static function permToString($perm) {
		if (is_string($perm)) return $perm;

		$perm = (int)$perm;
		$result = '';
		$chars = array_flip(static::$charPerms);
		for ($flag = 1; $flag <= static::PERM_ALL; $flag <<= 1) {
			if ($perm & $flag) $result .= $chars[$flag];
		}
		return $result;
	}

	public static function permToInt($perm) {
		if (!is_string($perm)) return (int)$perm;

		$result = 0;
		foreach (str_split($perm, 1) as $char) {
			if (isset(static::$charPerms[$char])) $result |= static::$charPerms[$char];
		}
		return $result;
	}

	public function addNameChars($chars) {
		$this->extraNameChars .= $chars;
		$this->makeRes();
	}

	protected function makeRes() {
		$firstChars = static::$defaultFirstNameChars . $this->extraNameChars;
		$lastChars = static::$defaultLastNameChars . $this->extraNameChars;
		$nameChars = static::$defaultNameChars . $this->extraNameChars;
		$wildCards = static::$wildCards;
		$rs = static::$rootSeparatorRe;
		$ds = static::$dirSeparatorRe;
		$nameMask = "[$firstChars](?:[$nameChars]*[$lastChars])?";
		$searchMask = "[$firstChars$wildCards](?:[$nameChars$wildCards]*[$lastChars$wildCards])?";
		$this->fileRe = "/^(?:($nameMask)$rs)?$ds?(((?:$nameMask$ds)*)($nameMask))?\$/u";
		$this->fileNameRe = "/^$nameMask\$/u";
		$this->rootNameRe = $this->fileNameRe;
		$this->searchNameRe = "/^$searchMask\$/u";
	}

	public function addRoot($name, $path, $perm) {
		if (isset($this->roots[$name])) return false;

		if (!preg_match($this->rootNameRe, $name)) return null;

		$path = realpath($path);
		if (!$path) return null;

		$perm = static::permToInt($perm);
		if ($perm & self::PERM_WRITE) $perm |= self::PERM_APPEND;
		$this->roots[$name] = [$path, $perm];
		if (!isset($this->defaultRoot)) {
			$this->defaultRoot = $name;
		}
		return true;
	}

	public function setDefaultRoot($name) {
		if (isset($this->roots[$name])) {
			$this->defaultRoot = $name;
		}
	}

	public function isAllowedName($name) {
		try {
			if (!file_exists($name) and @stat($name)) return false; // Win: nul, con, com#, etc
			else return true;
		} catch (\Exception $e) {
			return true;
		}
	}

	protected function fillPathInfo($pathInfo, $name, $isDir) {
		$pathInfo->name = $name;
		if (!preg_match($this->fileRe, $name, $match)) return self::ERR_NAME;

		if (!empty($match[1])) $root = $match[1];
		else $root = $this->defaultRoot;
		if (!isset($this->roots[$root])) return self::ERR_ROOT;

		$ds = DIRECTORY_SEPARATOR;
		$path = (isset($match[3]) ? $match[3] : '');
		$name = (isset($match[4]) ? $match[4] : '');
		$pathInfo->name = $root . self::RS . $path . $name;

		if ($ds <> static::DS) {
			$path = str_replace(static::DS, $ds, $path);
		}
		$realName = $this->encodeName($this->roots[$root][0] . $ds . $path . $name);
		$pathInfo->realName = $realName;
		$pathInfo->exists = file_exists($realName);
		if (!$this->isAllowedName($realName)) return self::ERR_TYPE;

		$pathInfo->isFile = is_file($realName);
		$pathInfo->isDir = is_dir($realName);
		if ($pathInfo->isDir xor $isDir) {
			return self::ERR_TYPE;
		}

		$perm = $this->roots[$root][1];
		if ($pathInfo->exists) {
			if (!is_readable($realName)) return self::ERR_PERM;

			if ($pathInfo->isFile) {
				$perm = (is_writable($realName) ? ($perm & (~self::PERM_CREATE)) : self::PERM_READ);
			}
			$pathInfo->perm = $perm;
		} else {
			if ($perm & self::PERM_CREATE) $pathInfo->perm = self::PERM_ALL;
			else return self::ERR_PATH;
		}

		return 0;
	}

	public function getFileInfo($name, $isDir = false) {
		$result = new Path;
		$result->error = $this->fillPathInfo($result, $name, $isDir);
		return $result;
	}

	public function getDirInfo($name) {
		return $this->getFileInfo($name, true);
	}

	public function isValidFileName($name) {
		return (bool)preg_match($this->fileNameRe, $name);
	}

	public function isValidSearchMask($mask) {
		return (bool)preg_match($this->searchNameRe, $mask);
	}

	public static function baseName($name) {
		$name = preg_split('/' . static::$rootDirSeparatorRe . '/', $name);
		$result = array_pop($name);
		return (strlen($result) ? $result : array_pop($result));
	}

	public static function parentPath($path) {
		$path = explode(static::DS, $path);
		if ($path[count($path) - 1] == '') array_pop($path);
		if (count($path) > 1) {
			array_pop($path);
			return implode(static::DS, $path);
		}

		$path = explode(static::RS, $path[0]);
		return ($path[0] . static::RS);
	}
}

