<?php

namespace ava12\tpl\machine;

class FunctionDef extends AbstractFunctionDef {
	public $vars = [];
	public $varNames = ['this', 'arg'];


	public function __construct($index = 0, $parentIndex = null, $isPure = false) {
		$this->index = $index;
		$this->parentIndex = $parentIndex;
		$this->pure = $isPure;
		$this->vars = [new Variable, new Variable];
	}
}
