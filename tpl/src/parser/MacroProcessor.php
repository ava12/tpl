<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\AbstractFunctionDef;
use \ava12\tpl\machine\IValue;
use \ava12\tpl\machine\IFunctionValue;
use \ava12\tpl\machine\IListValue;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\Machine;


class MacroProcessor implements IStringProcessor, IFunctionValue {
	protected $machine;
	protected $macros = [];
	protected static $macroRe = '/\\\\([^\\\\]*)\\\\/';

	public function __construct(Machine $machine) {
		$this->machine = $machine;
	}

	public function process($text, $stringType) {
		return preg_replace_callback(static::$macroRe, [$this, 'replaceCallback'], $text);
	}

	public function replaceCallback($match) {
		if (!strlen($match[1])) return '\\';

		$key = $match[1];
		if (!isset($this->macros[$key])) {
			throw new ParseException(ParseException::NO_MACRO, $key);
		}

		return $this->macros[$key];
	}

	public function initMetaFunction(AbstractFunctionDef $functionDef) {
		$functionDef->addVar('macros', new Variable($this, true));
	}

	public function getType() {
		return IValue::TYPE_FUNCTION_OBJ;
	}

	public function copy() {
		return $this;
	}

	public function getRawValue() {
		return $this;
	}

	public function isPure() {
		return false;
	}

	public function call($context, $container = null, $args = null) {
		if (!$args) return null;

		$macros = $this->machine->toList($args->getByIndex(1))->getValue();
		for ($i = 1; $i <= $macros->getCount(); $i++) {
			$key = $macros->getKeyByIndex($i);
			if (isset($key)) {
				$value = $this->machine->toString($macros->getByIndex($i));
				$this->macros[$key] = $value;
			}
		}
		return null;
	}
}
