<?php

namespace ava12\tpl\parser;

class ForHandler extends AbstractStateHandler {
	protected $hasStep = false;

	public function preReport($nonTerminal) {
		if ($nonTerminal == 'loop-body') {
			if (!$this->hasStep) $this->parser->emitNull();
			$this->parser->emitOpChunk(IParser::OP_FOR, IParser::CHUNK_TYPE_LOOP);
		}
	}

	public function postReport($nonTerminal) {
		switch ($nonTerminal) {
			case 'for-step':
				$this->hasStep = true;
			case 'for-start':
			case 'for-end':
				$this->parser->emitOp(IParser::OP_TO_NUMBER);
			break;
		}
	}

	public function finish() {
		$this->parser->endCodeChunk();
	}
}
