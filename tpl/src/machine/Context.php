<?php

namespace ava12\tpl\machine;

class Context implements IVarContainer {
	/** @var FunctionDef */
	protected $functionDef;
	protected $parentContext;
	/** @var Variable[] $vars */
	protected $vars = [];
	protected $varIndex; // {name => index}


	/**
	 * @param Context $parentContext
	 * @param FunctionDef $functionDef
	 * @param Variable|null $thisList
	 * @param IListValue|null $arg
	 */
	public function __construct($parentContext, $functionDef, $thisList = null, $arg = null) {
		if (!isset($thisList)) $thisList = new Variable;
		if (isset($arg)) $arg = $arg->copy();
		else $arg = new ListValue;
		$this->parentContext = $parentContext;
		$this->functionDef = $functionDef;
		$this->varIndex = array_flip($functionDef->varNames);
		foreach ($functionDef->vars as $index => $var) {
			$this->vars[$index] = ($var->isConst() ? $var : $var->copy());
		}

		if (isset($this->varIndex[FunctionDef::THIS_NAME])) {
			$this->vars[$this->varIndex[FunctionDef::THIS_NAME]] = $thisList;
		}

		if (!isset($this->varIndex[FunctionDef::ARG_NAME]) or !$arg->getCount()) return;

		$argVarIndex = $this->varIndex[FunctionDef::ARG_NAME];
		$this->vars[$argVarIndex] = new Variable($arg);
		$argIndex = array_slice($this->varIndex, $argVarIndex + 1, count($functionDef->args), true);
		if ($argIndex) {
			$emptyIndexes = range($argVarIndex + 1, $argVarIndex + count($argIndex));
		} else {
			$emptyIndexes = [];
		}

		for ($i = 1; $i <= $arg->getCount(); $i++) {
			/** @var Variable $var */
			$var = $arg->getByIndex($i)->copy();
			$name = $arg->getKeyByIndex($i);
			if (isset($name)) {
				if (!isset($argIndex[$name])) continue;

				$index = $argIndex[$name];
				$emptyIndexes = array_diff($emptyIndexes, [$argIndex[$name]]);
			} else {
				$index = array_shift($emptyIndexes);
				if (!isset($index)) continue;
			}

			if (!$this->vars[$index]->isRef() and $var->isRef()) {
				$var = $var->deref()->copy();
			}
			$this->vars[$index] = $var;
		}

		$this->vars[$argVarIndex] = new Variable($arg);
	}

	public function getFunctionDef() {
		return $this->functionDef;
	}

	public function findContext($functionIndex) {
		$result = $this;
		while ($result and $result->functionDef->index !== $functionIndex) {
			$result = $result->parentContext;
		}
		return $result;
	}

	public function addVar($index, $name, $var = null) {
		if (isset($this->varIndex[$name]) and $this->varIndex[$name] == $index) {
			if (!$var) return;

			if (!$this->vars[$index]->isNull() or $this->vars[$index]->isConst()) {
				throw new \RuntimeException("объект $name уже есть в данном контексте");
			}

			$this->vars[$index] = $var;
			return;
		}

		if ($index <> count($this->vars)) {
			throw new \RuntimeException('некорректный индекс добавленного объекта');
		}

		if (isset($this->varIndex[$name])) {
			throw new \RuntimeException("объект $name уже есть в данном контексте");
		}

		if (!$var) $var = new Variable;
		$this->vars[$index] = $var;
		$this->varIndex[$name] = $index;
	}

	public function getVarName($index) {
		$result = array_search($index, $this->varIndex);
		return (strlen($result) ? $result : null);
	}

	public function getByIndex($index) {
		return (isset($this->vars[$index]) ? $this->vars[$index] : null);
	}

	public function getByKey($key) {
		if (!isset($this->varIndex[$key])) return null;
		else return $this->vars[$this->varIndex[$key]];
	}

	/**
	 * @param int $index
	 * @param Variable $var
	 */
	public function setByIndex($index, $var) {
		if (isset($this->vars[$index]) and !$this->vars[$index]->isConst()) {
			if ($var->isRef()) $this->vars[$index] = $var;
			else {
				$value = $var->getValue();
				$this->vars[$index]->deref()->setValue($value);
				if ($var->isConst()) $this->vars[$index]->setIsConst();
			}
		}
	}

	public function setByKey($key, $var) {
		if (isset($this->varIndex[$key])) {
			$this->setByIndex($this->varIndex[$key], $var);
		}
	}

	public function isPublic() {
		return false;
	}
}
