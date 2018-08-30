<?php

namespace ava12\tpl\lib;

use \ava12\tpl\machine\RunException;
use \ava12\tpl\machine\IScalarValue;

class FilterRaw implements IScalarValue {
	protected $filterId;
	/** @var IScalarValue */
	protected $value;

	public function __construct($filterId, IScalarValue $value) {
		$this->filterId = $filterId;
		$this->value = $value;
	}

	public function getFilterId() {
		return $this->filterId;
	}

	public function copy() {
		return $this;
	}

	public function concat($value) {
		throw new RunException(RunException::SET_CONST);
	}

	public function __call($name, $args) {
		return call_user_func_array([$this->value, $name], $args);
	}

	public function getType() { return $this->value->getType(); }
	public function getRawValue() { return $this->value->getRawValue(); }

	public function isBool() { return $this->value->isBool(); }
	public function isNumber() { return $this->value->isNumber(); }
	public function isInt() { return $this->value->isInt(); }
	public function isString() { return $this->value->isString(); }
	public function asBool() { return $this->value->asBool(); }
	public function asNumber() { return $this->value->asNumber(); }
	public function asInt() { return $this->value->asInt(); }
	public function asString() { return $this->value->asString(); }
}
