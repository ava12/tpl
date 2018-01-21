<?php

namespace ava12\tpl\machine;

interface IListValue extends IValue, IVarContainer {
	public function getCount();
	public function getKeyByIndex($index);
	public function getIndexByKey($key);

	/**
	 * @param StackItem $value
	 * @param StackItem $key
	 * @param StackItem $index
	 * @return IIterator
	 */
	public function getIterator($value, $key, $index);

	/**
	 * @param Variable $var
	 */
	public function concat($var);
}
