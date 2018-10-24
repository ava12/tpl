<?php

namespace ava12\tpl\parser;

class ConstValueHandler extends AbstractStateHandler {
	protected $isRef = null;

	protected function init() {
		$func = $this->parser->getMachine()->makeExpression(true);
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
