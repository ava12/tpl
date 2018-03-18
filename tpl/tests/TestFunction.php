<?php

use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\IValue;
use \ava12\tpl\machine\RunException;
use \ava12\tpl\machine\Closure;
use \ava12\tpl\machine\NullValue;
use \ava12\tpl\machine\FunctionProxy;

class TestFunction {
	protected static $funcs = [
		'assert' => 'callAssert',
		'set' => 'callSet',
	];


	/**
	 * @param Machine $machine
	 */
	public static function setup($machine) {
		$mainFunc = $machine->getFunction();
		foreach (static::$funcs as $name => $handler) {
			FunctionProxy::inject($mainFunc, $name, [__CLASS__, $handler]);
		}
	}

	protected static function fail($failure, $path) {
		$path = implode(',', $path);
		$message = "получено: [$path] $failure";
		throw new RunException(RunException::CUSTOM, $message);
	}

	/**
	 * @param Variable $expected
	 * @param Variable $got
	 * @param array $path
	 */
	protected static function compare($expected, $got, $path = []) {
		if ($expected->isRef() <> $got->isRef()) {
			static::fail($got->isRef() ? 'ссылка' : 'значение', $path);
		}

		if ($expected->getType() <> $got->getType()) {
			static::fail($got->getType(), $path);
		}

		$expected = $expected->getValue();
		$got = $got->getValue();

		switch ($got->getType()) {
			case IValue::TYPE_NULL:
			break;

			case IValue::TYPE_SCALAR:
				$value = $got->getRawValue();
				if (is_int($value)) $value = (float)$value;
				$expectedValue = $expected->getRawValue();
				if (is_int($expectedValue)) $expectedValue = (float)$expectedValue;
				if ($value !== $expectedValue) {
					static::fail(gettype($value) . '(' . print_r($value, true) . ')', $path);
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
				if ($failure) static::fail($failure, $path);
			break;

			case IValue::TYPE_LIST:
				$gotCount = $got->getCount();
				if ($expected->getCount() <> $gotCount) {
					static::fail('неверная длина: ' . $gotCount, $path);
				}

				$pathLen = count($path);
				for ($i = 1; $i <= $gotCount; $i++) {
					$path[$pathLen] = $i;
					$gotKey = $got->getKeyByIndex($i);
					if ($expected->getKeyByIndex($i) !== $gotKey) {
						static::fail('неверный ключ: ' . $gotKey, $path);
					}

					static::compare($expected->getByIndex($i), $got->getByIndex($i), $path);
				}
			break;
		}
	}

	public static function callAssert($args) {
		$expected = (isset($args[0]) ? $args[0] : new Variable);
		$got = (isset($args[1]) ? $args[1] : new Variable);
		static::compare($expected, $got);

		echo '.';
		return null;
	}

	/**
	 * @param Variable[] $args
	 * @return null
	 */
	public static function callSet($args) {
		if (!isset($args[0])) return null;

		$value = (isset($args[1]) ? $args[1]->getValue() : NullValue::getValue());
		$args[0]->setValue($value);
		return null;
	}
}
