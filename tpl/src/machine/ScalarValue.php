<?php

namespace ava12\tpl\machine;

use \ava12\tpl\Util;

class ScalarValue implements IScalarValue {
	protected $value;


	public function __construct($value) {
		if (!isset($value)) $value = '';
		if (is_float($value) and !is_finite($value)) {
			throw new RunException(RunException::ARI, 'NaN');
		}

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

	public function asInt() {
		$value = $this->value;
		if (is_int($value)) return $value;

		try {
			if (is_float($value)) $value = Util::normalizeFloat($value);
			$result = (int)$value;
		} catch (\Exception $e) {
			$data = [$e->getMessage(), $e->getFile(), $e->getLine()];
			throw new RunException(RunException::ARI, $data);
		}
		return $result;
	}

	public function asString() { return (string)$this->value; }
	public function getRawValue() { return $this->value; }
}
