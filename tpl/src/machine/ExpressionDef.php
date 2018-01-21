<?php

namespace ava12\tpl\machine;

class ExpressionDef extends AbstractFunctionDef {
	public $index = null;
	public $parentIndex = 0;
	public $varNames = [];

	public function __construct($isPure = false) {
		$this->pure = $isPure;
	}
}
