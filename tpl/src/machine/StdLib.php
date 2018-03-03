<?php

namespace ava12\tpl\machine;

use \ava12\tpl\Util;

class StdLib {
	protected static $defaultFunc = [1 => 0, 2 => true];

	// {имя: [обработчик, имена|количество?, чистая?]}
	protected static $funcs = [
		'chr' => ['callChr'],
		'not' => ['callNot', 1],
		'ord' => ['callOrd', 1],
		'strlen' => ['callStrlen', 1],
		'substr' => ['callSubstr', 3],
		'trim' => ['callTrim', 1],
	];

	/** @var Machine */
	protected $machine;


	/**
	 * @param Machine $machine
	 */
	public function __construct($machine) {
		$this->machine = $machine;
	}

	/**
	 * @param Machine $machine
	 */
	public static function setup($machine) {
		$instance = new static($machine);
		$mainFunc = $machine->getFunction();
		foreach (static::$funcs as $name => $params) {
			$params += static::$defaultFunc;
			if (is_array($params[1])) {
				$cnt = count($params[1]);
				$names = $params[1];
			} else {
				$cnt = (int)$params[1];
				$names = [];
			}
			FunctionProxy::inject($mainFunc, $name, [$instance, $params[0]], $cnt, $params[2], $names);
		}
	}

	// not(b)
	public function callNot($args) {
		return !$this->machine->toBool($args[0]);
	}

	// ord(строка): код Unicode первого символа строки
	public function callOrd($args) {
		/** @var Variable[] $args */
		if ($args[0]->isNull()) return null;

		$char = $this->machine->toString($args[0]);
		if (!strlen($char)) return null;

		return new Variable(new ScalarValue(Util::ord($char)));
	}

	// chr(код, ...): строка из символов с указанными кодами
	public function callChr($args) {
		if (!isset($args[0])) return null;

		$result = '';
		foreach ($args as $arg) {
			$result .= Util::chr($this->machine->toInt($arg));
		}
		return new Variable(new ScalarValue($result));
	}

	// strlen(строка): длина строки (в символах)
	public function callStrlen($args) {
		/** @var Variable[] $args */
		if ($args[0]->isNull()) return null;

		return mb_strlen($this->machine->toString($args[0]));
	}

	protected function fixItemRange($total, &$start, &$length) {
		if ($start <= 0) {
			$start += $total;
		}
		if ($start <= 0) {
			$start = 1;
		} elseif ($start > $total) {
			$length = 0;
		}

		if ($length <= 0) {
			$length = 0;
			return;
		}

		$tail = $start + $length - 1 - $total;
		if ($tail > 0) {
			$length -= $tail;
		}
	}

	//substr(строка, первый_индекс, длина?): подстрока
	public function callSubstr($args) {
		/** @var Variable[] $args */
		$text = $this->machine->toString($args[0]);
		$start = $this->machine->toInt($args[1]);
		$len = ($args[2]->isNull() ? mb_strlen($text) : $this->machine->toInt($args[2]));
		$this->fixItemRange(mb_strlen($text), $start, $len);
		return ($len ? mb_substr($text, $start - 1, $len) : '');
	}

	// trim(строка)
	public function callTrim($args) {
		/** @var Variable[] $args */
		if ($args[0]->isNull()) return null;

		$re = "/^[ \x09\x0a\x0d\x0c\xc2\xa0]+|[ \x09\x0a\x0d\x0c\xc2\xa0]+\$/u";
		return preg_replace($re, '', $this->machine->toString($args[0]));
	}
}
