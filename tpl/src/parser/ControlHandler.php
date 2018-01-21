<?php

namespace ava12\tpl\parser;

class ControlHandler extends AbstractStateHandler {
	protected $op;
	protected $tokenOps = [
		'exit' => Parser::OP_EXIT,
		'return' => Parser::OP_RETURN,
		'continue' => Parser::OP_CONTINUE,
		'break' => Parser::OP_BREAK,
		'while' => Parser::OP_WHILE,
		'until' => Parser::OP_UNTIL,
	];

	public function useToken($token) {
		if (isset($this->tokenOps[$token->type])) {
			$this->op = $this->tokenOps[$token->type];
		}
	}

	public function finish() {
		$this->parser->emitOp($this->op);
	}
}
