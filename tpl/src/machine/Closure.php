<?php

namespace ava12\tpl\machine;

class Closure implements IValue {
	/** @var FunctionDef|IFunctionValue */
	public $func;
	public $context;

	/**
	 * @param FunctionDef|IFunctionValue $func
	 * @param Context|null $context
	 */
	public function __construct($func, $context = null) {
		$this->func = $func;
		$this->context = $context;
	}

	public function getType() {
		return IValue::TYPE_CLOSURE;
	}

	public function copy() {
		return $this;
	}

	public function isPure() {
		return $this->func->isPure();
	}

	public function getRawValue() {
		return $this->func;
	}
}
