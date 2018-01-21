<?php

namespace ava12\tpl\parser;

class ObjectHandler extends AbstractStateHandler {
	protected $isOperation = false;

	public function postReport($nonTerminal) {
		if ($nonTerminal <> 'set' and $nonTerminal <> 'concat') return;

		$this->isOperation = true;
		$parser = $this->parser;
		if ($nonTerminal == 'set') {
			$parser->emitOp(Parser::OP_SET);
		} else {
			$parser->emitOp(Parser::OP_CONCAT);
			$parser->emitOp(Parser::OP_DROP);
		}
	}

	public function finish() {
		if (!$this->isOperation) $this->parser->emitOp(Parser::OP_CONCAT);
	}
}
