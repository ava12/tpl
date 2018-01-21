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
				if (!$this->isPure and $this->parser->functionDef->isPure()) {
					throw new ParseException(ParseException::IMPURE_SUB);
				}

				$this->parser->beginFunction($this->isPure);
			break;
		}
	}

	public function finish() {
		$parser = $this->parser;
		$funcDef = $parser->functionDef;
		$parser->endFunction();
		$parser->emitOp(Parser::OP_FUNC, $funcDef->index);
	}
}
