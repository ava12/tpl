<?php

use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\IValue;
use \ava12\tpl\machine\RunException;
use \ava12\tpl\machine\Closure;
use \ava12\tpl\machine\ScalarValue;
use \ava12\tpl\machine\NullValue;
use \ava12\tpl\Util;

class TestFunction implements \ava12\tpl\machine\IFunctionValue {
	// {name: is_pure}
	protected static $funcs = [
		'assert' => 'callAssert',
		'chr' => 'callChr',
		'ord' => 'callOrd',
		'set' => 'callSet',
	];

	protected static $pureFuncs = ['chr', 'ord'];

	protected $handlerName;
	protected $pure = false;
	/** @var Machine */
	protected $machine;

	public function __construct($machine, $func) {
		if (!isset(static::$funcs[$func])) {
			throw new \RuntimeException("неизвестный тип функции: $func");
		}

		$this->machine = $machine;
		$this->handlerName = static::$funcs[$func];
		$this->pure = in_array($func, static::$pureFuncs);
	}

	/**
	 * @param Machine $machine
	 */
	public static function setup($machine) {
		$mainFunc = $machine->getFunction();
		foreach (array_keys(static::$funcs) as $func) {
			$var = new Variable(new static($machine, $func), false, true);
			$mainFunc->addVar($func, $var);
		}
	}

	public function getType() { return \ava12\tpl\machine\IValue::TYPE_FUNCTION_OBJ; }
	public function copy() { return $this; }
	public function getRawValue() { return $this; }
	public function isPure() { return true; }

	public function call($context, $container = null, $args = null) {
		$argArray = [];
		if ($args) {
			/** @var \ava12\tpl\machine\IListValue $args */
			for ($i = 1; $i <= $args->getCount(); $i++) {
				$argArray[] = $args->getByIndex($i);
			}
		}

		return call_user_func([$this, $this->handlerName], $argArray);
	}

	protected function fail($failure, $path) {
		$path = implode(',', $path);
		$message = "получено: [$path] $failure";
		throw new RunException(RunException::CUSTOM, $message);
	}

	/**
	 * @param Variable $expected
	 * @param Variable $got
	 * @param array $path
	 */
	protected function compare($expected, $got, $path = []) {
		if ($expected->isRef() <> $got->isRef()) {
			$this->fail($got->isRef() ? 'ссылка' : 'значение', $path);
		}

		if ($expected->getType() <> $got->getType()) {
			$this->fail($got->getType(), $path);
		}

		$expected = $expected->getValue();
		$got = $got->getValue();

		switch ($got->getType()) {
			case IValue::TYPE_NULL:
			break;

			case IValue::TYPE_SCALAR:
				$value = $got->getRawValue();
				if ($value !== $expected->getRawValue()) {
					$this->fail(gettype($value) . '(' . print_r($value, true) . ')', $path);
				}
			break;

			case IValue::TYPE_CLOSURE:
				$failure = null;
				/** @var Closure $expected */
				/** @var Closure $got */
				if ($expected->func !== $got->func) {
					$failure = 'неверная функция';
				} elseif ($expected->context !== $got->context) {
					$failure = 'неверный контекст';
				}
				if ($failure) $this->fail($failure, $path);
			break;

			case IValue::TYPE_LIST:
				$gotCount = $got->getCount();
				if ($expected->getCount() <> $gotCount) {
					$this->fail('неверная длина: ' . $gotCount, $path);
				}

				$pathLen = count($path);
				for ($i = 1; $i <= $gotCount; $i++) {
					$path[$pathLen] = $i;
					$gotKey = $got->getKeyByIndex($i);
					if ($expected->getKeyByIndex($i) !== $gotKey) {
						$this->fail('неверный ключ: ' . $gotKey, $path);
					}

					$this->compare($expected->getByIndex($i), $got->getByIndex($i), $path);
				}
			break;
		}
	}

	protected function callAssert($args) {
		$expected = (isset($args[0]) ? $args[0] : new Variable);
		$got = (isset($args[1]) ? $args[1] : new Variable);
		$this->compare($expected, $got);

		echo '.';
		return null;
	}

	protected function callOrd($args) {
		if (!isset($args[0])) return null;

		$char = $this->machine->toScalar($args[0])->getValue()->asString();
		return new Variable(new ScalarValue(Util::ord($char)));
	}

	protected function callChr($args) {
		if (!isset($args[0])) return null;

		$code = $this->machine->toScalar($args[0])->getValue()->asInt();
		return new Variable(new ScalarValue(Util::chr($code)));
	}

	/**
	 * @param Variable[] $args
	 * @return null
	 */
	protected function callSet($args) {
		if (!isset($args[0])) return null;

		$value = (isset($args[1]) ? $args[1]->getValue() : NullValue::getValue());
		$args[0]->setValue($value);
		return null;
	}
}
