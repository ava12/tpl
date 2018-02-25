<?php

namespace ava12\tpl\machine;

use \ava12\tpl\Util;

class StdLib {
	protected static $defaultFunc = [1 => true, 2 => []];

	// {имя: [обработчик, чистая?, имена?]}
	protected static $funcs = [
		'chr' => ['callChr'],
		'ord' => ['callOrd'],
		'substr' => ['callSubstr'],
		'strlen' => ['callStrlen'],
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
			FunctionProxy::inject($mainFunc, $name, [$instance, $params[0]], $params[1], $params[2]);
		}
	}

	// ord(строка): код Unicode первого символа строки
	public function callOrd($args) {
		if (!isset($args[0])) return null;

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
		if (!isset($args[0])) return null;

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

	/*
		substr(строка, первый_индекс, длина?): подстрока
	*/
	public function callSubstr($args) {
		if (count($args) < 2) {
			throw new RunException(RunException::WRONG_CALL, 'substr');
		}

		$text = $this->machine->toString($args[0]);
		$start = $this->machine->toInt($args[1]);
		$len = (isset($args[2]) ? $this->machine->toInt($args[2]) : mb_strlen($text));
		$this->fixItemRange(mb_strlen($text), $start, $len);
		return ($len ? mb_substr($text, $start - 1, $len) : '');
	}
}
