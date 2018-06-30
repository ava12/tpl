<?php

namespace ava12\tpl\parser;

class ItemHandler extends AbstractStateHandler {
	public function preReport($nonTerminal) {
		if ($nonTerminal == 'item-value') $this->parser->emitOp(IParser::OP_TO_VALUE);
	}

	public function postReport($nonTerminal) {
		if ($nonTerminal == 'item-value') $this->parser->emitOp(IParser::OP_ITEM);
	}
}
