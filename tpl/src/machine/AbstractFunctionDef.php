<?php

namespace ava12\tpl\machine;

use \ava12\tpl\parser\ParseException;

abstract class AbstractFunctionDef implements IValue {
	public $index = 0;
	public $parentIndex = null;
	/** @var CodeChunk[] $codeChunks*/
	protected $codeChunks = [];
	protected $pure = false;
	/** @var string[] $args */
	public $args = [];
	/** @var Variable[] $vars */
	public $vars = [];
	public $varNames = [];


	public function isPure() {
		return $this->pure;
	}

	public function getType() {
		return IValue::TYPE_FUNCTION;
	}

	public function copy() {
		return $this;
	}

	public function getRawValue() {
		return $this;
	}

	public function addCodeChunk($type = CodeChunk::TYPE_MISC) {
		$index = count($this->codeChunks);
		$result = new CodeChunk($index, $type);
		$this->codeChunks[$index] = $result;
		return $result;
	}

	public function getCodeChunk($index = 0) {
		return $this->codeChunks[$index];
	}

	public function addArg($name, $var) {
		if (in_array($name, $this->varNames)) {
			throw new ParseException(ParseException::DUPLICATE_NAME, $name);
		}

		$this->args[] = $name;
		return $this->addVar($name, $var);
	}

	public function addVar($name, $var = null) {
		$index = $this->getVarIndex($name);
		if ($index) {
			if (!$this->vars[$index]->isNull()) {
				throw new ParseException(ParseException::DUPLICATE_NAME, $name);
			}

			if ($var) $this->vars[$index] = $var;

		} else {
			$index = count($this->vars);
			$this->varNames[$index] = $name;
			$this->vars[$index] = ($var ?: new Variable);
		}

		return $index;
	}

	public function getVarIndex($name) {
		$names = array_flip($this->varNames);
		if (isset($names[$name])) return $names[$name];
		else return null;
	}

	public function getVar($index) {
		return (isset($this->vars[$index]) ? $this->vars[$index] : null);
	}
}
