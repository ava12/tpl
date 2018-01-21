<?php

namespace ava12\tpl\parser;

class ScalarHandler extends AbstractStateHandler {
	public function useToken($token) {
		switch ($token->type) {
			case Token::TYPE_NUMBER:
				$this->parser->emitNumber($token->value, $token);
				break;

			case Token::TYPE_TRUE:
			case Token::TYPE_FALSE:
				$this->parser->emitBool($token->type == Token::TYPE_TRUE, $token);
				break;

			default:
				$this->parser->emitString($token->value, $token);
				break;
		}
	}
}
