<?php

namespace ava12\tpl\parser;

class ListHandler extends AbstractStateHandler {
	protected $elementCount = 0;
	protected $gotElement = false;

	public function preReport($nonTerminal) {
		if ($nonTerminal == 'list-element') {
			$this->elementCount++;
			$this->gotElement = true;
		}
	}

	public function useToken($token) {
		if ($token->type == Token::TYPE_COMMA) {
			if (!$this->gotElement) {
				$this->parser->emitNull($token);
				$this->elementCount++;
			}
			$this->gotElement = false;
		}
	}

	public function finish() {
		if ($this->nonTerminal == 'call') {
			$this->parser->emitOp(IParser::OP_CALL, $this->elementCount);
		} else {
			$this->parser->emitOp(IParser::OP_MAKE_LIST, $this->elementCount);
		}
	}
}
