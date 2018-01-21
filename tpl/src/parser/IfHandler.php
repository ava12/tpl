<?php

namespace ava12\tpl\parser;

class IfHandler extends AbstractStateHandler {
  protected $indexes = [];
	protected $nonTerminals = ['if-body', 'loop-if-body', 'else-case', 'loop-else-case'];

	public function preReport($nonTerminal) {
		if (!in_array($nonTerminal, $this->nonTerminals)) return;

		$parser = $this->parser;
		$chunk = $parser->beginCodeChunk();
		$this->indexes[] = $chunk->index;
	}

	public function postReport($nonTerminal) {
		if (in_array($nonTerminal, $this->nonTerminals)) {
			$this->parser->endCodeChunk();
		}
	}

	public function finish() {
		$this->parser->emitOp(Parser::OP_IF, $this->indexes);
	}
}
