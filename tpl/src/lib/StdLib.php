<?php

namespace ava12\tpl\lib;

use \ava12\tpl\AbstractException;
use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\RunException;
use \ava12\tpl\machine\ScalarValue;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\IValue;
use \ava12\tpl\machine\IListValue;
use \ava12\tpl\machine\ListValue;
use \ava12\tpl\machine\NullValue;
use \ava12\tpl\Util;
use \ava12\tpl\env\Env;

class StdLib implements ILib {
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
		'<' => ['callLt', 2],
		'<<' => ['callSal', 2],
		'<=' => ['callLe', 2],
		'<>' => ['callNe', 2],
		'==' => ['callEq', 2],
		'>' => ['callGt', 2],
		'>=' => ['callGe', 2],
		'>>' => ['callSar', 2],
		'^' => ['callXor'],
		'^^' => ['callBXor'],
		'~~' => ['callBNot', 1],
		'call' => ['callCall', ['func', 'args', 'container']],
		'ceil' => ['callCeil', 1],
		'chr' => ['callChr'],
		'combine' => ['callCombine', ['keys', 'values']],
		'count' => ['callCount', 1],
		'data' => ['callData', 1],
		'div' => ['callIDiv', 2],
		'error' => ['callError', 1],
		'floor' => ['callFloor', 1],
		'index' => ['callIndex', 2],
		'int' => ['callInt', 1],
		'isconst' => ['callIsconst', 1],
		'isref' => ['callIsref', 1],
		'isset' => ['callIsset', 1],
		'key' => ['callKey', 2],
		'keys' => ['callKeys', 1],
		'length' => ['callStrlen', 1],
		'mod' => ['callIMod', 2],
		'ord' => ['callOrd', 1],
		'replace' => ['callReplace', 2],
		'round' => ['callRound', 1],
		'scalar' => ['callScalar', 1],
		'slice' => ['callSlice', ['l', 'start', 'count']],
		'sort' => ['callSort', 2],
		'splice' => ['callSplice', ['l', 'start', 'count', 'insert']],
		'substr' => ['callSubstr', 3],
		'trim' => ['callTrim', 1],
		'typeof' => ['callTypeof', 1],
		'values' => ['callValues', 1],
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

	public static function setup(Env $env) {
		$machine = $env->machine;
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

		return $instance;
	}

	protected function mapException(\Exception $e) {
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
				if ($arg->getType() == IValue::TYPE_NULL) $arg = new ScalarValue(false);
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

// - логика -------------------------------------------------------------------

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

// - арифметика ---------------------------------------------------------------

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
		return floor(Util::normalizeFloat($this->machine->toNumber($args[0])));
	}

	// ceil(n)
	public function callCeil($args) {
		/** @var Variable[] $args */
		return ceil(Util::normalizeFloat($this->machine->toNumber($args[0])));
	}

// - сравнения ----------------------------------------------------------------

	// ==(a, b)
	public function callEq($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toScalar($args[0])->getValue()->getRawValue();
		$b = $this->machine->toScalar($args[1])->getValue()->getRawValue();
		return Util::compareScalars($a, $b);
	}

	// <>(a, b)
	public function callNe($args) {
		return !$this->callEq($args);
	}

	// >(a, b)
	public function callGt($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toNumber($args[0]);
		$b = $this->machine->toNumber($args[1]);
		return (Util::compareFloats($a, $b) > 0);
	}

	// <=(a, b)
	public function callLe($args) {
		return !$this->callGt($args);
	}

	// >=(a, b)
	public function callGe($args) {
		/** @var Variable[] $args */
		$a = $this->machine->toNumber($args[0]);
		$b = $this->machine->toNumber($args[1]);
		return (Util::compareFloats($a, $b) >= 0);
	}

	// <(a, b)
	public function callLt($args) {
		return !$this->callGe($args);
	}

// - строки -------------------------------------------------------------------

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

// - списки -------------------------------------------------------------------

	// count(l)
	public function callCount($args) {
		/** @var Variable[] $args */
		return $this->machine->toList($args[0])->getValue()->getCount();
	}

	// slice(l, start?, count?)
	public function callSlice($args) {
		/** @var Variable[] $args */
		/** @var IListValue $list */
		$list = $this->machine->toList($args[0])->getValue()->copy();
		$start = ($args[1]->isNull() ? 1 : $this->machine->toInt($args[1]));
		$count = ($args[2]->isNull() ? null : $this->machine->toInt($args[2]));
		return new Variable($list->slice($start, $count));
	}

	// splice(l, start?, count?, insert?)
	public function callSplice($args) {
		/** @var Variable[] $args */
		/** @var IListValue $list */
		$list = $this->machine->toList($args[0])->getValue()->copy();
		$start = ($args[1]->isNull() ? 1 : $this->machine->toInt($args[1]));
		$count = ($args[2]->isNull() ? null : $this->machine->toInt($args[2]));
		$insert = $this->machine->toList($args[3])->getValue();
		return new Variable($list->splice($start, $count, $insert));
	}

	// key(l, index)
	public function callKey($args) {
		/** @var Variable[] $args */
		$list = $this->machine->toList($args[0])->getValue();
		$index = $this->machine->toScalar($args[1]);
		if ($index->isNull()) return null;
		else return $list->getKeyByIndex($index->getValue()->asInt());
	}

	// index(l, key)
	public function callIndex($args) {
		/** @var Variable[] $args */
		$list = $this->machine->toList($args[0])->getValue();
		$key = $this->machine->toString($args[1]);
		return $list->getIndexByKey($key);
	}

	// keys(l)
	public function callKeys($args) {
		/** @var Variable[] $args */
		$result = new ListValue;
		$keys = $this->machine->toList($args[0])->getValue()->getKeys();
		foreach ($keys as $v) {
			$v = (isset($v) ? new ScalarValue($v) : NullValue::getValue());
			$result->addItem(new Variable($v));
		}
		return new Variable($result);
	}

	// values(l)
	public function callValues($args) {
		/** @var Variable[] $args */
		/** @var IListValue $list */
		$list = $this->machine->toList($args[0])->getValue()->copy();
		return new Variable(new ListValue($list->getValues()));
	}

	// combine(keys, values)
	public function callCombine($args) {
		/** @var Variable[] $args */
		$keys = $this->machine->toList($args[0])->getValue()->getValues();
		$values = $this->machine->toList($args[1])->getValue()->getValues();
		$dcnt = count($values) - count($keys);
		if ($dcnt) {
			if ($dcnt > 0) $p = &$keys;
			else $p = &$values;
			for ($i = abs($dcnt); $i > 0; $i--) $p[] = new Variable;
			unset($p);
		}
		$result = new ListValue;
		foreach ($keys as $i => $k) {
			$key = ($k->isNull() ? null : $this->machine->toString($k));
			$result->addItem($values[$i]->copy(), $key);
		}
		return new Variable($result);
	}

	// sort(l, func?)
	// func: (v1, v2, k1, k2, i1, i2)
	public function callSort($args) {
		/** @var Variable[] $args */
		$callback = $args[1];
		if ($callback->isNull()) {
			$callback = $this->machine->getRootContext()->getByKey('-');
		}

		$listVar = $this->machine->toList($args[0])->copy();
		/** @var IListValue $list */
		$list = $listVar->getValue()->copy();
		$keys = $this->callKeys([$listVar])->getValue()->getValues();
		$values = $list->getValues();
		/** @var Variable[] $indexes */
		$indexes = [];
		for ($i = 1; $i <= count($values); $i++) {
			$indexes[] = new Variable(new ScalarValue($i));
		}

		usort($indexes, function(Variable $a, Variable $b) use ($keys, $values, $callback) {
			$indexA = $a->getValue()->getRawValue() - 1;
			$indexB = $b->getValue()->getRawValue() - 1;
			$args = [$values[$indexA], $values[$indexB], $keys[$indexA], $keys[$indexB], $a, $b];
			$result = $this->machine->callVar($callback, new ListValue($args));
			$result = $this->machine->toNumber($result);
			if (!$result) return 0;
			else return ($result > 0 ? 1 : -1);
		});

		$result = new ListValue;
		foreach ($indexes as $item) {
			$index = $item->getValue()->getRawValue() - 1;
			$result->addItem($values[$index], $keys[$index]->getValue()->getRawValue());
		}

		return new Variable($result);
	}

// - типы ---------------------------------------------------------------------

	// isset(a)
	public function callIsset($args) {
		/** @var Variable[] $args */
		return !$args[0]->isNull();
	}

	// typeof(a)
	public function callTypeof($args) {
		/** @var Variable[] $args */
		$value = $args[0]->getValue();
		$type = $value->getType();
		switch ($type) {
			case IValue::TYPE_NULL:
				return 'null';

			case IValue::TYPE_LIST:
				return 'list';

			case IValue::TYPE_FUNCTION:
			case IValue::TYPE_FUNCTION_OBJ:
			case IValue::TYPE_CLOSURE:
				return 'function';

			case IValue::TYPE_SCALAR:
				if ($value->isBool()) return 'bool';
				if ($value->isNumber()) return 'number';
				if ($value->isString()) return 'string';

				throw new \RuntimeException('неизвестный скалярный тип: ' . gettype($value->getRawValue()));

			default:
				throw new \RuntimeException("неизвестный тип значения: \"$type\"");
		}
	}

	// data(a)
	public function callData($args) {
		/** @var Variable[] $args */
		return $this->machine->toData($args[0]);
	}

	// scalar(a)
	public function callScalar($args) {
		/** @var Variable[] $args */
		return $this->machine->toScalar($args[0]);
	}

// - прочее -------------------------------------------------------------------

	// isconst(@a)
	public function callIsconst($args) {
		/** @var Variable[] $args */
		return $args[0]->deref()->isConst();
	}

	// isref(a)
	public function callIsref($args) {
		/** @var Variable[] $args */
		return $args[0]->isRef();
	}

	// call(func, args, @container)
	public function callCall($args) {
		/** @var Variable[] $args */
		$argList = $this->machine->toList($args[1])->getValue();
		$thisVar = ($args[2]->isNull() ? null : $this->machine->toList($args[2]->copy()));
		return $this->machine->callVar($args[0], $argList, $thisVar);
	}

	// error(text)
	public function callError($args) {
		/** @var Variable[] $args */
		$text = $this->machine->toString($args[0]);
		throw new RunException(RunException::CUSTOM, $text);
	}
}
