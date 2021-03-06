<?php

namespace ava12\tpl\cli;

use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\IValue;
use \ava12\tpl\machine\IScalarValue;
use \ava12\tpl\machine\IListValue;
use \ava12\tpl\machine\RunException;
use \ava12\tpl\machine\Closure;
use \ava12\tpl\machine\NullValue;
use \ava12\tpl\lib\FunctionProxy;
use \ava12\tpl\Util;
use \ava12\tpl\env\Env;
use \ava12\tpl\lib\ILib;
use \ava12\tpl\lib\FileSys;


class TestLib implements ILib {
	const INDENT = '  ';

	protected static $funcs = [
		'assert' => 'callAssert',
		'bytes' => 'callBytes',
		'set' => 'callSet',
		'prepareFs' => 'callPrepareFs',
		'dump' => 'callDump',
	];

	/** @var FileSys */
	protected $fs;

	public function __construct($fileSys) {
		$this->fs = $fileSys;
	}

	public static function setup(Env $env) {
		$instance = new static($env->fileSys);
		$mainFunc = $env->machine->getFunction();
		foreach (static::$funcs as $name => $handler) {
			FunctionProxy::inject($mainFunc, $name, [$instance, $handler]);
		}

		return $instance;
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
		if ($expected->isRef() xor $got->isRef()) {
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
				$expectedValue = $expected->getRawValue();
				if (!Util::compareScalars($value, $expectedValue)) {
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
					$gotKey = $got->getKeyByIndex($i);
					$path[$pathLen] = $i;
					if ($expected->getKeyByIndex($i) !== $gotKey) {
						$this->fail('неверный ключ: ' . $gotKey, $path);
					}

					if (isset($gotKey)) $path[$pathLen] .= ':' . $gotKey;
					$this->compare($expected->getByIndex($i), $got->getByIndex($i), $path);
				}
			break;
		}
	}

	public function callAssert($args) {
		$expected = (isset($args[0]) ? $args[0] : new Variable);
		$got = (isset($args[1]) ? $args[1] : new Variable);
		$this->compare($expected, $got);

		echo '.';
		return null;
	}

	/**
	 * @param Variable[] $args
	 * @return null
	 */
	public function callSet($args) {
		if (!isset($args[0])) return null;

		$value = (isset($args[1]) ? $args[1]->getValue() : NullValue::getValue());
		$args[0]->setValue($value);
		return null;
	}

	protected function remove($name) {
		if (is_file($name)) {
			unlink($name);
			return;
		}

		$list = glob($name . DIRECTORY_SEPARATOR . '*');
		foreach ($list as $item) {
			$this->remove($item);
		}

		rmdir($name);
	}

	protected function prepareDir($path, $content) {
		$encodedPath = $this->fs->encodeName($path);
		if (!is_dir($encodedPath)) {
			mkdir($encodedPath);
		}
		$found = glob($encodedPath . '*');
		foreach ($found as $name) {
			$decodedName = $this->fs->decodeName($name);
			$baseName = explode(DIRECTORY_SEPARATOR, $decodedName);
			$baseName = array_pop($baseName);
			if (!isset($content[$baseName])) {
				$this->remove($name);
			}
		}

		foreach ($content as $name => $value) {
			$fullName = $path . $name;
			if (is_array($value)) {
				$this->prepareDir($fullName . DIRECTORY_SEPARATOR, $value);
			} else {
				file_put_contents($this->fs->encodeName($fullName), $value);
			}
		}
	}


	/**
	 * @param Variable[] $args
	 * @return null
	 */
	public function callPrepareFs($args) {
		$ds = DIRECTORY_SEPARATOR;
		$root = realpath(__DIR__ . "$ds..$ds..{$ds}tests$ds" . 'files');
		if (!is_dir($root)) {
			mkdir($root);
		}

		if (!$args[0]->isContainer()) {
			throw new RunException(RunException::VAR_TYPE, $args[0]->getType());
		}

		$fs = $this->asArray($args[0]->getValue());
		foreach ($fs as $name => $data) {
			$data = array_values($data);
			$path = $root . $ds . $name;
			$this->prepareDir($path . $ds, $data[1]);
			$this->fs->addRoot($name, $path, $data[0]);
		}
		$this->fs->setDefaultRoot(array_keys($fs)[0]);

		return null;
	}

	/**
	 * @param IListValue $var
	 * @return array
	 */
	protected function asArray($var) {
		$result = [];
		$cnt = $var->getCount();
		for ($i = 1; $i <= $cnt; $i++) {
			$key = $var->getKeyByIndex($i);
			$el = $var->getByIndex($i);
			if ($el->isContainer()) {
				$v = $this->asArray($el->getValue());
			} else {
				$v = $el->asScalar()->getRawValue();
			}

			if (isset($key)) {
				$result[$key] = $v;
			} else {
				$result[] = $v;
			}
		}


		return $result;
	}

	/**
	 * @param Variable[] $args
	 * @return null
	 */
	public function callDump($args) {
		echo PHP_EOL;
		$this->dump($args[0]);
		return null;
	}

	/**
	 * @param Variable $var
	 * @param string $indent
	 */
	protected function dump($var, $indent = '') {
		if ($var->isConst()) echo '!';
		echo $var->getObjectIndex();
		if ($var->isRef()) {
			echo ' @ ';
			$this->dump($var->deref(), $indent);
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
					$this->dump($value->getByIndex($i), $newIndent);
				}
				echo $indent . ':]';
			break;
		}
		echo PHP_EOL;
	}

	/**
	 * @param Variable[] $args
	 * @return string
	 */
	public function callBytes($args) {
		$result = [];
		foreach ($args as $v) {
			$result[] = chr($v->asScalar()->asInt() & 0xff);
		}

		return implode('', $result);
	}
}
