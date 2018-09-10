<?php

namespace ava12\tpl\parser;

class FunctionHandler extends AbstractStateHandler {
	protected $isPure = false;

	public function useToken($token) {
		switch ($token->type) {
			case 'pure':
				$this->isPure = true;
            break;

			case 'function':
				$this->parser->beginFunction($this->isPure);
			break;
		}
	}

	public function finish() {
		$parser = $this->parser;
		$funcDef = $parser->getFunctionDef();
		$parser->endFunction();
		$parser->emitOp(IParser::OP_FUNC, $funcDef->index);
	}
}
