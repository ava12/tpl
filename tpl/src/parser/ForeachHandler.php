<?php

namespace ava12\tpl\parser;

class ForeachHandler extends AbstractStateHandler {
	protected $optObjects = ['each-value', 'each-key', 'each-index', 'loop-body'];
	protected $expectedOpt = [];

	protected  function init() {
		$this->expectedOpt = $this->optObjects;
	}

	public function preReport($nonTerminal) {
		$parser = $this->parser;

		if (!in_array($nonTerminal, $this->optObjects)) return;

		while ($nonTerminal <> array_shift($this->expectedOpt)) {
			$parser->emitNull();
		}

		if ($nonTerminal == 'loop-body') {
			$parser->emitOpChunk(IParser::OP_FOREACH, IParser::CHUNK_TYPE_LOOP);
		}
	}

	public function postReport($nonTerminal) {
		if ($nonTerminal == 'each-object') $this->parser->emitOp(IParser::OP_TO_VALUE);
	}

	public function finish() {
		$this->parser->endCodeChunk();
	}
}
