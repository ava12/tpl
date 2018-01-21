<?php

namespace ava12\tpl\parser;

class WhenHandler extends AbstractStateHandler {
	protected $valueCnt = 0;

	public function preReport($nonTerminal) {
		if ($nonTerminal <> 'when-body') return;

		$parser = $this->parser;
		if ($this->valueCnt > 1) {
			$parser->emitOp(Parser::OP_MAKE_LIST, $this->valueCnt);
		}
		$parser->emitOpChunk(Parser::OP_WHEN);
	}

	public function postReport($nonTerminal) {
		switch ($nonTerminal) {
			case 'when-value':
				$this->parser->emitOp(Parser::OP_TO_SCALAR);
				$this->valueCnt++;
			break;

			case 'when-body':
				$this->parser->endCodeChunk();
			break;
		}
	}
}
