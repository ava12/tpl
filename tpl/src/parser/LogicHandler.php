<?php

namespace ava12\tpl\parser;

class LogicHandler extends AbstractStateHandler {
	protected $started = false;
	protected $breakOp;

	protected function init() {
		$this->breakOp = ($this->nonTerminal == 'and-value' ? IParser::OP_WHILE : IParser::OP_UNTIL);
		$this->parser->emitOpChunk(IParser::OP_DO, IParser::CHUNK_TYPE_DO);
	}

	public function preReport($nonTerminal) {
		if ($nonTerminal <> 'and-item' and $nonTerminal <> 'or-item') return;

		if (!$this->started) {
			$this->started = true;
			return;
		}

		$parser = $this->parser;
		$parser->emitOp(IParser::OP_DUP);
		$parser->emitOp($this->breakOp);
		$parser->emitOp(IParser::OP_DROP);
	}

	public function finish() {
		$this->parser->endCodeChunk();
	}
}
