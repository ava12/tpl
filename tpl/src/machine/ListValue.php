<?php

namespace ava12\tpl\machine;

class ListValue implements IListValue {
	protected $value;
	protected $className;

	public function __construct($value = []) {
		if (!is_array($value)) $value = [$value];
		/** @var Variable[] $value */
		$this->value = $value;
		$this->className = get_class($this);
	}

	public static function encodeKey($key) {
		return ':' . $key;
	}

	public static function decodeKey($key) {
		$prefix = substr($key, 0, 1);
		return ($prefix == ':' ? substr($key, 1) : null);
	}

	public function getType() { return IValue::TYPE_LIST; }
	public function getCount() { return count($this->value); }

	public function copy() {
		$newValue = [];
		foreach ($this->value as $key => $var) {
			$newValue[$key] = $var->copy();
		}
		return new static($newValue);
	}

	protected function normalizeIndex($index) {
		$count = count($this->value);
		if ($index <= 0) $index = $count - $index;
		if ($index < 1 or $index > $count) return null;
		else return ($index - 1);
	}

	public function getIndexByKey($key) {
		$key = static::encodeKey($key);
		if (!array_key_exists($key, $this->value)) return null;

		$result = array_search($key, array_keys($this->value));
		return ($result === false ? null : $result + 1);
	}

	public function getKeyByIndex($index) {
		$index = $this->normalizeIndex($index);
		if (!isset($index)) return null;
		else return static::decodeKey(array_keys($this->value)[$index]);
	}

	public function getByIndex($index) {
		$index = $this->normalizeIndex($index);
		return (isset($index) ? array_values($this->value)[$index] : null);
	}

	public function getByKey($key) {
		$key = $this->encodeKey($key);
		return (isset($this->value[$key]) ? $this->value[$key] : null);
	}

	public function getIterator($value, $key, $index) {
		return new ListIterator($this, $value, $key, $index);
	}

	/**
	 * @param Variable $var
	 */
	public function concat($var) {
		$value = $var->getValue();

		if ($value->getType() <> IValue::TYPE_LIST) {
			$this->value[] = $var;
			return;
		}

		if (get_class($value) == $this->className) {
			/** @var ListValue $value */
			$this->value = array_merge($this->value, $value->value);
			return;
		}

		/** @var IListValue $value */
		for ($i = 1; $i <= $value->getCount(); $i++) {
			$item = $value->getByIndex($i);
			$key = $value->getKeyByIndex($i);
			if (isset($key)) $this->value[static::encodeKey($key)] = $item;
			else $this->value[] = $item;
		}
	}

	/**
	 * @param string $key
	 * @param Variable $var
	 */
	protected function set($key, $var) {
		if ($var->isRef()) $this->value[$key] = $var;
		elseif (!isset($this->value[$key])) {
			$this->value[$key] = $var->copy();
		} else {
			$this->value[$key]->deref()->setValue($var->getValue()->copy());
		}
	}

	public function setByIndex($index, $var) {
		$index--;
		if ($index < 0) return;

		if ($index >= count($this->value)) {
			for ($i = $index - count($this->value); $i > 0; $i--) {
				$this->value[] = new Variable;
			}
			$this->value[] = $var;
			return;
		}

		$key = array_keys($this->value)[$index];
		if (!$this->value[$key]->isConst()) $this->set($key, $var);
	}

	public function setByKey($key, $var) {
		$key = static::encodeKey($key);
		if (!isset($this->value[$key]) or !$this->value[$key]->isConst()) {
			$this->set($key, $var);
		}
	}

	public function isPublic() {
		return true;
	}

	public function addItem($var, $key = null) {
		if (isset($key)) {
			$key = static::encodeKey($key);
			if (!isset($this->value[$key]) or !$this->value[$key]->isConst) {
				$this->value[$key] = $var;
			}
		} else {
			$this->value[] = $var;
		}
	}

	public function getRawValue() { return $this->value; }
}
