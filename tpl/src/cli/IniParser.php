<?php

namespace ava12\tpl\cli;

use ava12\tpl\Util;

class IniParser {
	protected $env = ['' => '%'];
	protected $result = [];
	protected $maxNestLevel = 3;

	public function setEnv($name, $value) {
		$this->env[$name] = $this->processValue((string)$value);
	}

	public function setValue($name, $value) {
		$path = explode('.', $name);
		$p = &$this->result;
		while ($path) {
			$key = array_shift($path);
			if (!isset($p[$key])) $p[$key] = [];
			$p = &$p[$key];
		}
		$p = $value;
	}

	public function parseFile($name) {
		if (!is_file($name) or !is_readable($name)) {
			throw new \RuntimeException("Невозможно прочитать файл $name");
		}

		$file = file_get_contents($name);
		if (!Util::checkEncoding($file)) {
			throw new \RuntimeException("Файл $name должен иметь кодировку UTF-8");
		}

		$result = parse_ini_string($file, true);
		if (!$result) {
			throw new \RuntimeException("Некорректный формат файла $name");
		}

		if (isset($result['env'])) {
			foreach ($result['env'] as $key => $value) {
				$this->env[$key] = $this->processValue($value);
			}
			unset($result['env']);
		}

		$keyLists = array_fill(0, $this->maxNestLevel + 1, []);
		foreach (array_keys($result) as $key) {
			$nestLevel = substr_count($key, '.');
			if ($nestLevel > $this->maxNestLevel) {
				throw new \RuntimeException("Слишком большой уровень вложенности: [$key]");
			}

			$keyLists[$nestLevel][] = $key;
		}

		for ($i = 0; $i <= $this->maxNestLevel; $i++) {
			foreach ($keyLists[$i] as $k) {
				$this->setValue($k, $result[$k]);
			}
		}
	}

	public function getResult($name = null) {
		$result = $this->processValue($this->result);
		if (isset($name)) {
			foreach (explode('.', $name) as $key) {
				if (!is_array($result) or !isset($result[$key])) {
					return null;
				}

				$result = $result[$key];
			}
			$result = (isset($result[$name]) ? $result[$name] : []);
		}

		return $result;
	}

	protected function processValue($value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = $this->processValue($v);
			}
		} else {
			$value = preg_replace_callback('/%([A-Za-z0-9_]*)%/', function($m) {
				if (empty($m[1])) {
					return '%';
				} elseif (isset($this->env[$m[1]])) {
					return $this->env[$m[1]];
				} else throw new \RuntimeException("Неизвестная переменная: %{$m[1]}%");

			}, $value);
		}

		return $value;
	}
}