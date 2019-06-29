<?php

namespace ava12\tpl\env;

class LibConfig {
	protected static $stdLibs = ['std', 'regexp', 'meta', 'file'];
	const NAME_SEPARATOR = ';';

	public $lib = []; // {имя: имя_класса}

	public function __construct(array $config = []) {
		if ($config) {
			$this->apply($config);
		}
	}

	public function apply(array $config) {
		if (empty($config['lib'])) {
			$libNames = static::$stdLibs;
		} else {
			$libNames = $config['lib'];
			if (!is_array($libNames)) {
				$libNames = explode(self::NAME_SEPARATOR, $libNames);
			}
		}

		$errors = [];
		foreach ($libNames as $name) {
			$className = '\\ava12\\tpl\\lib\\' . ucfirst($name) . 'Lib';
			if (class_exists($className)) {
				$this->lib[$name] = $className;
			}
			else $errors[] = $name;
		}

		if ($errors) {
			$errors = implode(', ', array_unique($errors));
			throw new \RuntimeException('неизвестные библиотеки: ' . $errors);
		}
	}

	public function addLib($name, $lib) {
		$this->lib[$name] = $lib;
	}
}
