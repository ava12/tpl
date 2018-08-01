<?php

namespace ava12\tpl\lib;

use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\RunException;
use \ava12\tpl\machine\IListValue;
use \ava12\tpl\machine\ListValue;
use \ava12\tpl\machine\ScalarValue;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\Closure;

class Filter implements IListValue {
	use \ava12\tpl\machine\ObjectCounter;

	const FUNC_INDEX = 2;
	const FUNC_KEY = 'func';
	const MAX_INDEX = 3;

	/** @var Machine */
	protected $machine;
	protected $content = '';
	protected $buffer = '';
	/** @var Variable|null */
	protected $func;
	/** @var ListValue */
	protected $list;


	public function __construct(Machine $machine, Variable $func) {
		$this->machine = $machine;
		$this->func = $func;
		$this->newIndex();
		$this->list = new ListValue;
		$this->addProxy('content', 0);
		$this->list->addItem($func->copy(), 'func');
		$this->addProxy('raw', 1);
		$this->addProxy('clear', 0);
	}

	protected function addProxy($name, $argCount) {
		$callback = [$this, 'call' . ucfirst($name)];
		$proxy = new FunctionProxy($callback, $argCount);
		$closure = new Closure($proxy);
		$this->list->addItem(new Variable($closure, true), $name);
	}

	public function copy() {
		return $this;
	}

	public function getRawValue() {
		return $this;
	}

	public function setByIndex($index, $var) {
		if ($index <> self::FUNC_INDEX) {
			throw new RunException(RunException::SET_CONST);
		}

		$this->list->setByIndex($index, $var);
		$this->func = $var;
	}

	public function setByKey($key, $var) {
		if ($key <> self::FUNC_KEY) {
			throw new RunException(RunException::SET_CONST);
		}

		$this->list->setByKey($key, $var);
		$this->func = $var;
	}

	public function concat($var) {
		$var = $this->machine->toData($var);
		$value = $var->getValue();
		if ($value instanceof FilterRaw) {
			$this->addRaw($value);
		} elseif (!$var->isContainer()) {
			$this->buffer .= $this->machine->toString($var);
		} else {
			/** @var IListValue $value */
			for ($i = 1; $i <= $value->getCount(); $i++) {
				$var = $value->getByIndex($i);
				$v = $var->getValue();
				if ($v instanceof FilterRaw) $this->addRaw($v);
				else $this->buffer .= $this->machine->toString($var);
			}
		}
	}

	protected function addRaw(FilterRaw $raw) {
		if ($raw->getFilterId() === $this->objectIndex) {
			$this->content .= $this->escape($this->buffer);
			$this->buffer = '';
			$this->content .= $raw->asString();
		} else {
			$this->buffer .= $raw->asString();
		}
	}

	protected function escape($text) {
		if (!strlen($text)) return '';

		$args = new ListValue;
		$args->addItem(new Variable(new ScalarValue($text)));
		$result = $this->machine->callVar($this->func, $args);
		return $this->machine->toString($result);
	}

	public function callContent() {
		return $this->content . $this->escape($this->buffer);
	}

	public function callRaw($args) {
		/** @var Variable[] $args */
		$var = $this->machine->toScalar($args[0]);
		return new Variable(new FilterRaw($this->objectIndex, $var->getValue()));
	}

	public function callClear() {
		$this->content = '';
		$this->buffer = '';
		return null;
	}

	public function getType() { return $this->list->getType(); }

	public function getByIndex($index) { return $this->list->getByIndex($index); }
	public function getByKey($key) { return $this->list->getByKey($key); }
	public function isPublic() { return $this->list->isPublic(); }

	public function getCount() { return $this->list->getCount(); }
	public function getKeyByIndex($index) { return $this->list->getKeyByIndex($index); }
	public function getIndexByKey($key) { return $this->list->getIndexByKey($key); }
	public function getIterator($value, $key, $index) { return $this->list->getIterator($value, $key, $index); }
	public function getKeys() { return $this->list->getKeys(); }
	public function getValues() { return $this->list->getValues(); }
	public function slice($start, $count) { return $this->list->slice($start, $count); }
	public function splice($start, $count, $insert) { return $this->list->splice($start, $count, $insert); }
}
