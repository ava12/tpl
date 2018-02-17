<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\AbstractFunctionDef;

interface IStringProcessor {
	public function process($text, $stringType);
	public function initMetaFunction(AbstractFunctionDef $functionDef);
}
