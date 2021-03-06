<?php

namespace ava12\tpl\machine;

class Reference extends Variable {
	protected $isReference = true;

	/** @noinspection PhpMissingParentConstructorInspection */
	/**
	 * @param Variable $target
	 * @param bool $isConst
	 */
	public function __construct($target, $isConst = false) {
		$this->value = $target;
		$this->isConstant = $isConst;
		$this->newIndex();
	}

	public function setIsConst() {
		if ($this->isConstant) return $this;

		$this->isConstant = true;
		$this->value->setIsConst();
		return $this;
	}

	public function deref() {
		return $this->value;
	}

	public function ref() {
		return $this;
	}

	public function copy() {
		return new static($this->value);
	}
}
