<?php

namespace ava12\tpl\parser;

class WrapHandler extends AbstractStateHandler {
	protected function init() {
		$parser = $this->parser;
		switch ($this->nonTerminal) {
			case 'compound-value':
				$parser->emitNull();
			break;

			case 'do-block':
				$parser->emitOpChunk(IParser::OP_DO, IParser::CHUNK_TYPE_DO);
			break;

			case 'field-body':
				$parser->emitOp(IParser::OP_TO_VALUE);
			break;
		}
	}

	public function postReport($nonTerminal) {
		if ($this->nonTerminal == 'pair' and $nonTerminal == 'compound-key') {
			$this->parser->emitOp(IParser::OP_TO_STRING);
		}
	}

	public function finish() {
		$parser = $this->parser;
		switch ($this->nonTerminal) {
			case 'do-block':
				$parser->endCodeChunk();
			break;

			case 'field-body':
				$parser->emitOp(IParser::OP_ITEM);
			break;

			case 'pair':
				$parser->emitOp(IParser::OP_MAKE_PAIR);
			break;

			case 'reference':
				$parser->emitOp(IParser::OP_MAKE_REF);
			break;

			case 'deref':
				$parser->emitOp(IParser::OP_DEREF);
			break;

			case 'compound-expression':
			case 'deref-expression':
			case 'literal-expression':
			case 'reference-expression':
				$parser->emitOp(IParser::OP_CONCAT);
			break;
		}
	}
}
