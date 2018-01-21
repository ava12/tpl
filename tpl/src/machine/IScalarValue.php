<?php

namespace ava12\tpl\machine;

interface IScalarValue extends IValue {
	public function isBool();
	public function isNumber();
	public function isInt();
	public function isString();
	public function asBool();
	public function asNumber();
	public function asInt();
	public function asString();
	public function concat($value);
}
