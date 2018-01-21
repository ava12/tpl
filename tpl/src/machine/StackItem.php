<?php

namespace ava12\tpl\machine;

class StackItem {
	const TYPE_VALUE = 'value'; // Variable, IVarContainer|null, null|int|string
	const TYPE_NULL = 'null'; // Variable, IVarContainer, array
	const TYPE_PAIR = 'pair'; // Variable, null, string

	public $type;
	/** @var Variable|null */
	public $value;
	/** @var IVarContainer|null */
	public $container;
	public $path;


	public function __construct($value, $type = self::TYPE_VALUE, $container = null, $path = null) {
		$this->value = (isset($value) ? $value : new Variable);
		$this->type = $type;
		$this->container = $container;
		$this->path = $path;
	}

	/**
	 * @return Variable
	 */
	public function asVar() {
		return $this->value->deref();
	}

	/**
	 * @return Variable
	 */
	public function asRef() {
		return $this->value->ref();
	}

	public function getKey() {
		return ($this->type == self::TYPE_PAIR ? $this->path : null);
	}

	protected function checkTarget($isRef) {
		$var = $this->value;
		$var = ($isRef ? $var->ref() : $var->deref());
		if ($var->isConst()) {
			throw new RunException(RunException::SET_CONST);
		}
	}

	/**
	 * @param IVarContainer $container
	 * @param int|string $path
	 * @param Variable $var
	 */
	protected function setByPath($container, $path, $var) {
		if (is_int($path)) $container->setByIndex($path, $var);
		else $container->setByKey($path, $var);
	}

	protected function fixNullTarget() {
		$container = $this->container;
		$key = $this->path[0];
		$item = (is_int($key) ? $container->getByIndex($key) : $container->getByKey($key));
		if ($item) {
			$list = new ListValue;
			if (!$item->isNull()) $list->addItem($item->copy());
			$this->setByPath($container, $key, new Variable($list));
			array_shift($this->path);
			$container = $list;
		}

		while (count($this->path) > 1) {
			$item = new Variable(new ListValue);
			$path = array_shift($this->path);
			$this->setByPath($container, $path, $item);
			$container = $item->getValue();
		}

		$this->type = self::TYPE_VALUE;
		$this->container = $container;
		$this->path = $this->path[0];
	}

	/**
	 * @param Variable $var
	 */
	public function setVar($var) {
		$this->checkTarget($var->isRef());
		if (!$this->container) return;

		if ($this->type == self::TYPE_NULL) $this->fixNullTarget();
		if ($this->container and isset($this->path)) {
			$this->setByPath($this->container, $this->path, $var);
		}
	}
}
