<?php

use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\IValue;
use \ava12\tpl\machine\RunException;
use \ava12\tpl\machine\Closure;
use \ava12\tpl\machine\NullValue;
use \ava12\tpl\machine\FunctionProxy;
use \ava12\tpl\Util;


class TestFunction {
	const INDENT = '  ';

	protected static $funcs = [
		'assert' => 'callAssert',
		'set' => 'callSet',
		'prepareFs' => 'callPrepareFs',
		'dump' => 'callDump',
	];

	/** @var FileSys */
	protected static $fs;

	/**
	 * @param Machine $machine
	 * @param FileSys $fileSys
	 */
	public static function setup($machine, $fileSys) {
		static::$fs = $fileSys;
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
		if ($expected->isRef() xor $got->isRef()) {
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
				$expectedValue = $expected->getRawValue();
				if (!Util::compareScalars($value, $expectedValue)) {
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
					$gotKey = $got->getKeyByIndex($i);
					$path[$pathLen] = $i;
					if ($expected->getKeyByIndex($i) !== $gotKey) {
						static::fail('неверный ключ: ' . $gotKey, $path);
					}

					if (isset($gotKey)) $path[$pathLen] .= ':' . $gotKey;
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

	protected static function prepareDir($path, $content) {
		$encodedPath = static::$fs->encodeName($path);
		if (!is_dir($encodedPath)) mkdir($encodedPath);
		$found = glob($encodedPath . '*');
		foreach ($found as $name) {
			$decodedName = static::$fs->decodeName($name);
			$baseName = explode(DIRECTORY_SEPARATOR, $decodedName);
			$baseName = array_pop($baseName);
			if (!isset($content[$baseName])) {
				unlink($name);
			}
		}

		foreach ($content as $name => $value) {
			$fullName = $path . $name;
			if (is_array($value)) {
				static::prepareDir($fullName . DIRECTORY_SEPARATOR, $value);
			} else {
				file_put_contents(static::$fs->encodeName($fullName), $value);
			}
		}
	}

	public static function callPrepareFs() {
		$ds = DIRECTORY_SEPARATOR;
		$root = __DIR__ . $ds . 'files' . $ds;
		$fs = require(__DIR__ . $ds . 'files.php');
		foreach ($fs as $name => $data) {
			static::prepareDir($root . $name . $ds, $data[1]);
		}
	}

	/**
	 * @param Variable[] $args
	 * @return null
	 */
	public static function callDump($args) {
		echo PHP_EOL;
		static::dump($args[0]);
	}

	/**
	 * @param Variable $var
	 * @param string $indent
	 */
	protected static function dump($var, $indent = '') {
		if ($var->isConst()) echo '!';
		echo $var->getObjectIndex();
		if ($var->isRef()) {
			echo ' @ ';
			static::dump($var->deref(), $indent);
			return;
		}

		$value = $var->getValue();
		$type = $value->getType();
		echo ' ' . $type;
		switch ($type) {
			case IValue::TYPE_SCALAR:
				/** @var IScalarValue $value */
				$raw = $value->getRawValue();
				if (is_bool($raw)) echo ($raw ? ' true' : ' false');
				elseif (is_string($raw)) echo ' "' . $raw . '"';
				else echo ' ' . $raw;
			break;

			case IValue::TYPE_LIST:
				$newIndent = $indent . self::INDENT;
				echo ' [:' . PHP_EOL;
				/** @var IListValue $value */
				$cnt = $value->getCount();
				for ($i = 1; $i <= $cnt; $i++) {
					$key = $value->getKeyByIndex($i);
					echo $newIndent . '.' . $key . ': ';
					static::dump($value->getByIndex($i), $newIndent);
				}
				echo $indent . ':]';
			break;
		}
		echo PHP_EOL;
	}
}
