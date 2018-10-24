<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\FunctionDef;

interface IMetaEnv {
	/**
	 * @return FunctionDef
	 */
	public function getMetaFunction();
	public function runMetaFunction(FunctionDef $func);
}
