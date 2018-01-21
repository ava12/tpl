<?php

namespace ava12\tpl\machine;

class NullValue implements IScalarValue {
	protected static $value;

	public function getType() { return IValue::TYPE_NULL; }
	public function copy() { return $this; }
	public function getRawValue() { return null; }


	public static function getValue() {
		if (!static::$value) static::$value = new static;
		return static::$value;
	}

	public function isBool() { return false; }
	public function isNumber() { return false; }
	public function isInt() { return false; }
	public function isString() { return false; }
	public function asBool() { return false; }
	public function asNumber() { return 0; }
	public function asInt() { return 0; }
	public function asString() { return 0; }
	public function concat($value) {}
}
