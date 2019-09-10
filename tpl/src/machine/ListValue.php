<?php

namespace ava12\tpl\machine;

class ListValue implements IListValue {
	protected $value;
	protected $className;

	/**
	 * @param Variable|Variable[] $value
	 */
	public function __construct($value = []) {
		if (!is_array($value)) {
			$value = ($value->isNull() ? [] : [$value]);
		}
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
		if ($index <= 0) $index = $count + $index;
		if ($index < 1 or $index > $count) return null;
		else return ($index - 1);
	}

	public function getIndexByKey($key) {
		$key = static::encodeKey($key);
		if (!array_key_exists($key, $this->value)) return null;

		$result = array_search($key, array_keys($this->value), true);
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
			if (!isset($this->value[$key]) or !$this->value[$key]->isConst()) {
				$this->value[$key] = $var;
			}
		} else {
			$this->value[] = $var;
		}
	}

	public function getRawValue() {
		return $this->value;
	}

	public function getKeys() {
		$result = array_keys($this->value);
		foreach ($result as $i => $k) {
			$result[$i] = $this->decodeKey($k);
		}
		return $result;
	}

	public function getValues() {
		return array_values($this->value);
	}

	protected function normalizeStartCount(&$start, &$count) {
		$total = count($this->value);
		if (!isset($start)) $start = 1;
		if ($start > $total or (isset($count) and $count <= 0)) return false;

		if ($start <= 0) $start += $total;
		if (isset($count)) $end = $start + $count - 1;
		else $end = $total;

		if ($start < 1) $start = 1;
		if ($end > $total) $end = $total;
		if ($end < $start) return false;

		$start--;
		$count = $end - $start;
		return true;
	}

	public function slice($start, $count) {
		if (!$this->normalizeStartCount($start, $count)) return new ListValue;
		else return new ListValue(array_slice($this->value, $start, $count));
	}

	/**
	 * @param int $start
	 * @param int $count
	 * @param null|IListValue $insert
	 * @return ListValue
	 */
	public function splice($start, $count, $insert) {
		if (isset($insert)) $insert = $insert->getRawValue();
		else $insert = [];
		/** @var Variable[] $insert */
		if (!isset($count)) $count = count($insert);
		if (!$this->normalizeStartCount($start, $count)) return $this;

		$result = $this->value;
		array_splice($result, $start, $count);

		foreach(array_keys($insert) as $k) {
			if (!is_numeric($k) and isset($result[$k])) {
				$value = $insert[$k];
				unset($insert[$k]);
				$target = $result[$k];
				if ($target->isConst() or (!$value->isRef() and $target->deref()->isConst())) {
					continue;
				}

				$value = $value->copy();
				if ($value->isRef()) $result[$k] = $value;
				else $result[$k]->setValue($value->getValue());
			}
		}

		$this->value = array_merge(
			array_slice($result, 0, $start),
			$insert,
			array_slice($result, $start)
		);
	}
}
