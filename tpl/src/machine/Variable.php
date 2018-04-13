<?php

namespace ava12\tpl\machine;

class Variable {
	use ObjectCounter;

	protected static $funcDefTypes = [
		IValue::TYPE_FUNCTION,
		IValue::TYPE_FUNCTION_OBJ,
	];
	protected static $valueTypes = [
		IValue::TYPE_NULL,
		IValue::TYPE_SCALAR,
		IValue::TYPE_LIST,
	];

	protected static $scalarTypes = [
		IValue::TYPE_NULL,
		IValue::TYPE_SCALAR,
	];

	/** @var IValue|Variable */
	protected $value = null;
	protected $isReference = false;
	protected $isConstant = false;

	/**
	 * @param IValue|null $value
	 * @param bool $isConst
	 */
	public function __construct($value = null, $isConst = false) {
		if (!$value) $value = NullValue::getValue();
		$this->value = $value;
		$this->isConstant = $isConst;
		$this->newIndex();
	}

	public function isRef() {
		return $this->isReference;
	}

	public function isConst() {
		return $this->isConstant;
	}

	public function setIsConst() {
		if ($this->isConstant) return $this;

		$this->isConstant = true;
		if ($this->value->getType() == IValue::TYPE_LIST) {
			/** @var IListValue $value */
			$value = $this->value;
			for ($i = 1; $i <= $value->getCount(); $i++) {
				$value->getByIndex($i)->setIsConst();
			}
		}
		return $this;
	}

	public function deref() {
		return $this;
	}

	public function ref() {
		return new Reference($this);
	}

	/**
	 * @return IValue|IScalarValue|IListValue|IFunctionValue
	 */
	public function getValue() {
		return ($this->isReference ? $this->value->getValue() : $this->value);
	}

	public function getType() {
		return $this->getValue()->getType();
	}

	public function isPure() {
		$value = $this->getValue();

		switch ($value->getType()) {
			case IValue::TYPE_FUNCTION:
			case IValue::TYPE_FUNCTION_OBJ:
			case IValue::TYPE_CLOSURE:
				return $value->isPure();

			default:
				return true;
		}
	}

	/**
	 * @return Variable
	 */
	public function copy() {
		$value = $this->value;
		if (!$this->isConstant) $value = $value->copy();
		return new static($value);
	}

	public function isNull() {
		return ($this->getValue()->getType() == IValue::TYPE_NULL);
	}

	public function isFuncDef() {
		return in_array($this->getValue()->getType(), static::$funcDefTypes);
	}

	public function isCallable() {
		return ($this->getValue()->getType() == IValue::TYPE_CLOSURE);
	}

	public function isScalar() {
		return in_array($this->getValue()->getType(), static::$scalarTypes);
	}

	public function isContainer() {
		return ($this->getValue()->getType() == IValue::TYPE_LIST);
	}

	public function setValue($value) {
		$var = ($this->isReference ? $this->value : $this);
		if ($var->isConst()) {
			throw new RunException(RunException::SET_CONST);
		}

		$var->value = $value;
	}

	public function asScalar() {
		$value = $this->getValue();
		if (in_array($value->getType(), static::$scalarTypes)) return $value;

		throw new RunException(RunException::VAR_TYPE, $value->getType());
	}

	public function asBool() {
		$value = $this->getValue();
		if (in_array($value->getType(), static::$scalarTypes)) return $value->asBool();

		throw new RunException(RunException::VAR_TYPE, $value->getType());
	}
}
