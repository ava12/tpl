<?php

namespace ava12\tpl\lib;

use \ava12\tpl\machine\IValue;
use \ava12\tpl\machine\IListValue;
use \ava12\tpl\machine\IFunctionValue;
use \ava12\tpl\machine\FunctionDef;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\ScalarValue;


class FunctionProxy implements IFunctionValue {
	// function(Variable[] $args, IListValue $container, Context $context, $param)
	protected $callback;
	protected $purity;
	protected $param;
	protected $argIndex;
	protected $argCount;

	public function __construct($callback, $argCount = 0, $isPure = false, $argNames = [], $param = null) {
		$this->callback = $callback;
		$this->argCount = $argCount;
		$this->purity = $isPure;
		$this->param = $param;
		$this->argIndex = array_flip($argNames);
		unset($this->argIndex['']);
	}

	/**
	 * @param FunctionDef $funcDef
	 * @param string $name
	 * @param callable $callback
	 * @param int $argCount
	 * @param bool $isPure
	 * @param string[] $argNames
	 * @param mixed $param
	 */
	public static function inject($funcDef, $name, $callback, $argCount = 0, $isPure = false, $argNames = [], $param = null) {
		$proxy = new static($callback, $argCount, $isPure, $argNames, $param);
		$funcDef->addVar($name, new Variable($proxy, true));
	}

	public function getType() { return IValue::TYPE_FUNCTION_OBJ; }
	public function copy() { return $this; }
	public function getRawValue() { return $this; }
	public function isPure() { return $this->purity; }

	public function call($context, $container = null, $args = null) {
		/** @var Variable[] $argArray */
		$argArray = [];
		if ($this->argCount > 0) {
			for ($i = 0; $i < $this->argCount; $i++) {
				$argArray[$i] = new Variable;
			}
		}

		$extraArgs = [];
		$mapArgs = (bool)$this->argIndex;
		$resultIndex = 0;

		if ($args) {
			/** @var IListValue $args */
			for ($i = 1; $i <= $args->getCount(); $i++) {
				$arg = $args->getByIndex($i);
				if ($mapArgs) {
					$key = $args->getKeyByIndex($i);
					if (isset($key)) {
						if (isset($this->argIndex[$key])) $argArray[$this->argIndex[$key]] = $arg;
						else $extraArgs[$key] = $arg;
						continue;
					}
				}

				while (isset($argArray[$resultIndex]) and !$argArray[$resultIndex]->isNull()) {
					$resultIndex++;
				}
				$argArray[$resultIndex] = $arg;
				$resultIndex++;
			}
		}

		$argArray = array_merge($argArray, $extraArgs);

		$result = call_user_func($this->callback, $argArray, $container, $context, $this->param);
		if (isset($result)) {
			if (is_object($result)) $result = $result->copy();
			else $result = new Variable(new ScalarValue($result));
		}
		return $result;
	}
}
