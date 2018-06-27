<?php

namespace ava12\tpl\env;

class LibConfig {
	public $lib = []; // {имя: имя_класса}

	public function __construct(array $config = []) {
		if ($config) $this->apply($config);
	}

	public function apply(array $config) {
		if (empty($config['lib'])) return;

		$errors = [];
		foreach ((array)$config['lib'] as $name) {
			$className = '\\ava12\\tpl\\lib\\' . ucfirst($name) . 'Lib';
			if (class_exists($className)) $this->lib[$name] = $className;
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
