<?php

namespace ava12\tpl\machine;

interface IValue {
	const TYPE_NULL = 'null';
	const TYPE_SCALAR = 'scalar';
	const TYPE_LIST = 'list';
	const TYPE_FUNCTION = 'function';
	const TYPE_FUNCTION_OBJ = 'o-function';
	const TYPE_CLOSURE = 'closure';

	public function getType();
	public function copy();
	public function getRawValue();
}
