<?php

namespace ava12\tpl\parser;

class CaseHandler extends AbstractStateHandler {
	protected $hasDefault = false;

	public function postReport($nonTerminal) {
		if ($nonTerminal <> 'case-value') return;

		$this->parser->emitOpChunk(Parser::OP_CASE, Parser::CHUNK_TYPE_CASE);
	}

	public function preReport($nonTerminal) {
		if ($nonTerminal <> 'else-case') return;

		$this->hasDefault = true;
		$this->parser->emitOpChunk(Parser::OP_DEFAULT);
	}

	public function finish() {
		if ($this->hasDefault) $this->parser->endCodeChunk();
		$this->parser->endCodeChunk();
	}
}
