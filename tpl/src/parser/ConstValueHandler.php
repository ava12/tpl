<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\ExpressionDef;

class ConstValueHandler extends AbstractStateHandler {
	protected $isRef = null;

	protected function init() {
		$func = new ExpressionDef($this->nonTerminal <> 'const-object');
		$this->parser->insertFunction($func);
	}

	public function useToken($token) {
		if (!isset($this->isRef)) {
			$this->isRef = ($token->type == Token::TYPE_REF);
		}
	}

	public function finish() {
		$parser = $this->parser;
		if ($this->isRef) $parser->emitOp(IParser::OP_MAKE_REF);
		$parser->emitOp(IParser::OP_CONCAT);
		$funcDef = $parser->getFunctionDef();
		$parser->endFunction();
		$parser->setLastConstant($parser->getMachine()->computeExpression($funcDef));
	}
}
