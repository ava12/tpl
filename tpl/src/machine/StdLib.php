<?php

namespace ava12\tpl\machine;

use ava12\tpl\AbstractException;
use \ava12\tpl\Util;

class StdLib {
	protected static $defaultFunc = [1 => 0, 2 => true];

	// {имя: [обработчик, имена|количество?, чистая?]}
	protected static $funcs = [
		'&' => ['callAnd'],
		'&&' => ['callBAnd'],
		'*' => ['callMul'],
		'**' => ['callPow', 2],
		'+' => ['callAdd'],
		'-' => ['callSub'],
		'/' => ['callDiv'],
		'<<' => ['callSal', 2],
		'>>' => ['callSar', 2],
		'^' => ['callXor'],
		'^^' => ['callBXor'],
		'ceil' => ['callCeil', 1],
		'chr' => ['callChr'],
		'div' => ['callIDiv', 2],
		'floor' => ['callFloor', 1],
		'int' => ['callInt', 1],
		'length' => ['callStrlen', 1],
		'mod' => ['callIMod', 2],
		'not' => ['callBNot', 1],
		'ord' => ['callOrd', 1],
		'replace' => ['callReplace', 2],
		'round' => ['callRound', 1],
		'substr' => ['callSubstr', 3],
		'trim' => ['callTrim', 1],
		'|' => ['callOr'],
		'||' => ['callBOr'],
		'~' => ['callNot', 1],
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

	protected function mapException($e) {
		if ($e instanceof AbstractException) return $e;

		$data = [$e->getMessage(), $e->getFile(), $e->getFile()];
		return new RunException(RunException::ARI, $data);
	}

	/**
	 * @param Variable[] $args
	 * @param mixed $initial
	 * @param callable $func (mixed function(mixed $a, ScalarValue $b))
	 * @return mixed
	 */
	protected function reduceScalars($args, $initial, $func) {
		$result = $initial;

		try {
			foreach ($args as $arg) {
				$arg = $this->machine->toScalar($arg)->getValue();
				$result = $func($result, $arg);
				if (is_float($result) and !is_finite($result)) {
					throw new RunException(RunException::ARI, 'NaN');
				}
			}

		} catch (\Exception $e) {
			throw $this->mapException($e);
		}

		return $result;
	}

	// not(b)
	public function callBNot($args) {
		return !$this->machine->toBool($args[0]);
	}

	// &&(b, ...)
	public function callBAnd($args) {
		if (!$args) return false;

		return $this->reduceScalars($args, true, function ($a, ScalarValue $b) {
			return ($a and $b->asBool());
		});
	}

	// ||(b, ...)
	public function callBOr($args) {
		return $this->reduceScalars($args, false, function ($a, ScalarValue $b) {
			return ($a or $b->asBool());
		});
	}

	// ^^(b, ...)
	public function callBXor($args) {
		return $this->reduceScalars($args, false, function ($a, ScalarValue $b) {
			return ($a xor $b->asBool());
		});
	}


	// +(n, ...)
	public function callAdd($args) {
		if (!$args) return null;

		return $this->reduceScalars($args, 0, function ($a, ScalarValue $b) {
			return ($a + $b->asNumber());
		});
	}

	// -(a, b?)
	public function callSub($args) {
		/** @var Variable[] $args */
		foreach ($args as $i => $arg) $args[$i] = $this->machine->toNumber($arg);
		switch (count($args)) {
			case 0:
				return null;

			case 1:
				return (-$args[0]);

			default:
				return ($args[0] - $args[1]);
		}
	}

	// *(n, ...)
	public function callMul($args) {
		if (!$args) return null;

		return $this->reduceScalars($args, 1, function ($a, ScalarValue $b) {
			return ($a * $b->asNumber());
		});
	}

	// /(a, b?)
	public function callDiv($args) {
		/** @var Variable[] $args */
		foreach ($args as $i => $arg) $args[$i] = $this->machine->toNumber($arg);
		try {
			switch (count($args)) {
				case 0:
					return null;

				case 1:
					return (1 / $args[0]);

				default:
					return ($args[0] / $args[1]);
			}

		} catch (\Exception $e) {
			throw $this->mapException($e);
		}
	}

	// div(a, b)
	public function callIDiv($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toInt($args[0]);
		$b = $this->machine->toInt($args[1]);
		try {
			$rem = $a % $b;
			return (int)(($a - $rem) / $b);
		} catch (\Exception $e) {
			throw $this->mapException($e);
		}
	}

	// mod(a, b)
	public function callIMod($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toInt($args[0]);
		$b = $this->machine->toInt($args[1]);
		try {
			return $a % $b;
		} catch (\Exception $e) {
			throw $this->mapException($e);
		}
	}

	// **(a, b)
	public function callPow($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toNumber($args[0]);
		$b = $this->machine->toNumber($args[1]);
		try {
			return pow($a, $b);
		} catch (\Exception $e) {
			throw $this->mapException($e);
		}
	}

	// ~(i)
	public function callNot($args) {
		/** @var Variable[] $args */
		return ~$this->machine->toInt($args[0]);
	}

	// &(i, ...)
	public function callAnd($args) {
		if (!$args) return null;

		return $this->reduceScalars($args, -1, function ($a, ScalarValue $b) {
			return ($a & $b->asInt());
		});
	}

	// |(i, ...)
	public function callOr($args) {
		if (!$args) return null;

		return $this->reduceScalars($args, 0, function ($a, ScalarValue $b) {
			return ($a | $b->asInt());
		});
	}

	// ^(i, ...)
	public function callXor($args) {
		if (!$args) return null;

		return $this->reduceScalars($args, 0, function ($a, ScalarValue $b) {
			return ($a ^ $b->asInt());
		});
	}

	// <<(a, b?)
	public function callSal($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toInt($args[0]);
		if ($args[1]->isNull()) $b = 1;
		else $b = $this->machine->toInt($args[1]);
		if ($b <= 0) return $a;

		if ($b >= PHP_INT_SIZE * 8) return 0;

		try {
			return $a << $b;
		} catch (\Exception $e) {
			throw $this->mapException($e);
		}
	}

	// >>(a, b?)
	public function callSar($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toInt($args[0]);
		if ($args[1]->isNull()) $b = 1;
		else $b = $this->machine->toInt($args[1]);
		if ($b <= 0) return $a;

		if ($b >= PHP_INT_SIZE * 8) return ($a >= 0 ? 0 : -1);

		try {
			return $a >> $b;
		} catch (\Exception $e) {
			throw $this->mapException($e);
		}
	}

	// int(n)
	// floor(n)
	public function callInt($args) {
		/** @var Variable[] $args */
		return $this->machine->toInt($args[0]);
	}

	// round(n)
	public function callRound($args) {
		/** @var Variable[] $args */
		return round($this->machine->toNumber($args[0]));
	}

	// floor(n)
	public function callFloor($args) {
		/** @var Variable[] $args */
		return floor($this->machine->toNumber($args[0]));
	}

	// ceil(n)
	public function callCeil($args) {
		/** @var Variable[] $args */
		return ceil($this->machine->toNumber($args[0]));
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

	// replace(s, pairs)
	public function callReplace($args) {
		/** @var Variable[] $args */
		$result = $this->machine->toString($args[0]);
		$pairs = $this->machine->toList($args[1])->getValue();
		$filter = [];
		for ($i = 1; $i <= $pairs->getCount(); $i++) {
			$key = $pairs->getKeyByIndex($i);
			if (strlen($key)) {
				$filter[$key] = $this->machine->toString($pairs->getByIndex($i));
			}
		}

		if ($filter) $result = strtr($result, $filter);
		return $result;
	}

}
