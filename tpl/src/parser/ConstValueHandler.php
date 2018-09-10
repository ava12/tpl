<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\ExpressionDef;

class ConstValueHandler extends AbstractStateHandler {
	protected $isRef = null;

	protected function init() {
		$func = new ExpressionDef(true);
		$this->parser->insertFunction($func);
	}

	public function finish() {
		$parser = $this->parser;
		$funcDef = $parser->getFunctionDef();
		$parser->emitOp(IParser::OP_CONCAT);
		$parser->endFunction();
		$parser->setLastConstant($parser->getMachine()->computeExpression($funcDef));
	}
}
