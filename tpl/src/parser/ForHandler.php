<?php

namespace ava12\tpl\parser;

class ForHandler extends AbstractStateHandler {
	protected $hasStep = false;

	public function preReport($nonTerminal) {
		if ($nonTerminal == 'loop-body') {
			if (!$this->hasStep) $this->parser->emitNull();
			$this->parser->emitOpChunk(Parser::OP_FOR, Parser::CHUNK_TYPE_LOOP);
		}
	}

	public function postReport($nonTerminal) {
		switch ($nonTerminal) {
			case 'for-step':
				$this->hasStep = true;
			case 'for-start':
			case 'for-end':
				$this->parser->emitOp(Parser::OP_TO_NUMBER);
			break;
		}
	}

	public function finish() {
		$this->parser->endCodeChunk();
	}
}
