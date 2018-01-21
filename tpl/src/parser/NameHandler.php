<?php

namespace ava12\tpl\parser;

class NameHandler extends AbstractStateHandler {
	public function useToken($token) {
		$name = $token->value;
		$machine = $this->parser->machine;
		$funcDef = $this->parser->functionDef;
		$index = $funcDef->getVarIndex($name);
		if (isset($index)) {
			$this->parser->emitOp(Parser::OP_VAR, $index);
			return;
		}

		while (!isset($index) and isset($funcDef->parentIndex)) {
			$funcDef = $machine->getFunction($funcDef->parentIndex);
			$index = $funcDef->getVarIndex($name);
		}

		if (isset($index)) {
			$this->parser->emitOp(Parser::OP_VAR, [$index, $funcDef->index]);
		} else {
			throw new ParseException(ParseException::NO_NAME, $name);
		}
	}
}
