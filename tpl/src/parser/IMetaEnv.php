<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\ExpressionDef;

interface IMetaEnv {
	/**
	 * @return ExpressionDef
	 */
	public function getMetaFunction();
	public function runMetaFunction(ExpressionDef $func);
}
