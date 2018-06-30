<?php

namespace ava12\tpl\parser;

class ControlHandler extends AbstractStateHandler {
	protected $op;
	protected $tokenOps = [
		'exit' => IParser::OP_EXIT,
		'return' => IParser::OP_RETURN,
		'continue' => IParser::OP_CONTINUE,
		'break' => IParser::OP_BREAK,
		'while' => IParser::OP_WHILE,
		'until' => IParser::OP_UNTIL,
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
