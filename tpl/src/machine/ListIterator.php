<?php

namespace ava12\tpl\machine;

class ListIterator implements IIterator {
	protected $list;
	protected $valueTarget;
	protected $keyTarget;
	protected $indexTarget;

	protected $index = 1;

	/**
	 * @param IListValue $list
	 * @param StackItem $valueTarget
	 * @param StackItem $keyTarget
	 * @param StackItem $indexTarget
	 */
	public function __construct($list, $valueTarget, $keyTarget, $indexTarget) {
		$this->list = $list;
		$this->valueTarget = $valueTarget;
		$this->keyTarget = $keyTarget;
		$this->indexTarget = $indexTarget;
	}

	public function count() {
		return $this->list->getCount();
	}

	public function next() {
		$var = $this->list->getByIndex($this->index);
		if (!$var) return false;

		$key = $this->list->getKeyByIndex($this->index);
		$keyValue = (isset($key) ? new ScalarValue($key) : NullValue::getValue());
		$this->valueTarget->setVar($var);
		$this->indexTarget->setVar(new Variable(new ScalarValue($this->index)));
		$this->keyTarget->setVar(new Variable($keyValue));
		$this->index++;
		return true;
	}
}
