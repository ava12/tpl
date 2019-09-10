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

	/**
	 * @return array
	 */
	public function getKeys();

	/**
	 * @return Variable[]
	 */
	public function getValues();

	/**
	 * @param int|null $start
	 * @param int|null $count
	 * @return IListValue
	 */
	public function slice($start, $count);

	/**
	 * @param int|null $start
	 * @param int|null $count
	 * @param IListValue $insert
	 */
	public function splice($start, $count, $insert);
}
