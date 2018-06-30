<?php

namespace ava12\tpl\parser;

class NameHandler extends AbstractStateHandler {
	public function useToken($token) {
		$name = $token->value;
		$machine = $this->parser->getMachine();
		$funcDef = $this->parser->getFunctionDef();
		$index = $funcDef->getVarIndex($name);

		if (isset($index)) {
			$this->parser->emitOp(IParser::OP_VAR, $index);
			return;
		}

		$isPure = $funcDef->isPure();

		while (!isset($index) and isset($funcDef->parentIndex)) {
			$funcDef = $machine->getFunction($funcDef->parentIndex);
			$index = $funcDef->getVarIndex($name);
		}

		if (!isset($index)) {
			throw new ParseException(ParseException::NO_NAME, $name);
		}

		if ($isPure) {
			$var = $funcDef->getVar($index);
			if (!$var->isConst() or !$var->deref()->isConst()) {
				throw new ParseException(ParseException::IMPURE_DEF);
			}
		}

		$this->parser->emitOp(IParser::OP_VAR, [$index, $funcDef->index]);
	}
}
