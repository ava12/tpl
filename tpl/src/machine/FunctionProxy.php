<?php

namespace ava12\tpl\machine;

class FunctionProxy implements IFunctionValue {
	// function(Variable[] $args, IListValue $container, Context $context, $param)
	protected $callback;
	protected $purity;
	protected $param;
	protected $argIndex;

	public function __construct($callback, $isPure = false, $argNames = [], $param = null) {
		$this->callback = $callback;
		$this->purity = $isPure;
		$this->param = $param;
		$this->argIndex = array_flip($argNames);
		unset($this->argIndex['']);
	}

	/**
	 * @param AbstractFunctionDef $funcDef
	 * @param string $name
	 * @param callable $callback
	 * @param bool $isPure
	 * @param string[] $argNames
	 * @param mixed $param
	 */
	public static function inject($funcDef, $name, $callback, $isPure = false, $argNames = [], $param = null) {
		$proxy = new static($callback, $isPure, $argNames, $param);
		$funcDef->addVar($name, new Variable($proxy, true));
	}

	public function getType() { return IValue::TYPE_FUNCTION_OBJ; }
	public function copy() { return $this; }
	public function getRawValue() { return $this; }
	public function isPure() { return $this->purity; }

	public function call($context, $container = null, $args = null) {
		$argArray = [];
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

				while (isset($argArray[$resultIndex])) $resultIndex++;
				$argArray[$resultIndex] = $arg;
				$resultIndex++;
			}
		}

		$argArray = array_merge($argArray, $extraArgs);

		$result = call_user_func($this->callback, $argArray, $container, $context, $this->param);
		if (isset($result) and !is_object($result)) {
			$result = new Variable(new ScalarValue($result));
		}
		return $result;
	}
}
