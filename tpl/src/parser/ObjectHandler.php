<?php

namespace ava12\tpl\parser;

class ObjectHandler extends AbstractStateHandler {
	protected $isOperation = false;

	public function postReport($nonTerminal) {
		if ($nonTerminal <> 'set' and $nonTerminal <> 'concat') return;

		$this->isOperation = true;
		$parser = $this->parser;
		if ($nonTerminal == 'set') {
			$parser->emitOp(IParser::OP_SET);
		} else {
			$parser->emitOp(IParser::OP_CONCAT);
			$parser->emitOp(IParser::OP_DROP);
		}
	}

	public function finish() {
		if (!$this->isOperation) $this->parser->emitOp(IParser::OP_CONCAT);
	}
}
