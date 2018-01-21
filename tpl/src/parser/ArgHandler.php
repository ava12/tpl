<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\Variable;

class ArgHandler extends AbstractStateHandler {
	protected $byRef = false;
	protected $argName;
	/** @var null|Variable */
	protected $value;

	public function useToken($token) {
		switch ($token->type) {
			case Token::TYPE_REF:
				if ($this->parser->functionDef->isPure()) {
					throw new ParseException(ParseException::IMPURE_SIDE);
				} else {
					$this->byRef = true;
				}
			break;

			case Token::TYPE_NAME:
				$this->argName = $token->value;
			break;
		}

	}

	public function postReport($nonTerminal) {
		if ($nonTerminal == 'constant-value') {
			$this->value = $this->parser->lastConstant;
		}
	}

	public function finish() {
		$value = ($this->value);
		if (!isset($value)) {
			if ($this->byRef) $value = (new Variable)->ref();
		} else {
			$value = ($this->byRef ? $value->ref() : $value->deref());
		}
		$this->parser->functionDef->addArg($this->argName, $value);
	}
}
