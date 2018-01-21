<?php

namespace ava12\tpl\machine;

class ScalarValue implements IScalarValue {
	protected $value;


	public function __construct($value) {
		if (!isset($value)) $value = '';
		$this->value = $value;
	}

	public function getType() { return IValue::TYPE_SCALAR; }
	public function copy() { return new static($this->value); }

	/**
	 * @param IScalarValue $value
	 */
	public function concat($value) {
		$this->value .= $value->asString();
	}

	public function isBool() { return is_bool($this->value); }
	public function isNumber() { return (is_int($this->value) or is_float($this->value)); }
	public function isInt() { return is_int($this->value); }
	public function isString() { return is_string($this->value); }

	public function asBool() {
		return (bool)(is_string($this->value) ? strlen($this->value) : $this->value);
	}

	public function asNumber() {
		if (is_int($this->value) or is_float($this->value)) {
			return $this->value;
		} elseif (is_string($this->value) and strpbrk($this->value, '.eE') !== false) {
			return (float)$this->value;
		} else {
			return (int)$this->value;
		}
	}

	public function asInt() { return (int)((float)$this->value); }
	public function asString() { return (string)$this->value; }
	public function getRawValue() { return $this->value; }
}
