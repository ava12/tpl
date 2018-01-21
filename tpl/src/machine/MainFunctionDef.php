<?php

namespace ava12\tpl\machine;

class MainFunctionDef extends AbstractFunctionDef {
	public $vars = [];
	public $varNames = ['arg'];

	public function __construct() {
		$this->vars = [new Variable];
	}
}
