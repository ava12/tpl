<?php

namespace ava12\tpl\parser;

class VarDefHandler extends AbstractStateHandler {
	protected $isConst;
	protected $name;

	protected function init() {
		$this->isConst = ($this->nonTerminal == 'constant');
	}

	protected function addVar($name, $value = null) {
		$funcDef = $this->parser->getFunctionDef();
		$index = $funcDef->addVar($name, $value);
		if ($funcDef->index === 0) {
			$this->parser->getMachine()->getRootContext()->addVar($index, $name, $value);
		}
		$this->name = null;
	}

	public function useToken($token) {
		if ($token->type <> Token::TYPE_NAME) return;

		if ($this->name) $this->addVar($this->name);

		$this->name = $token->value;
		$funcDef = $this->parser->getFunctionDef();
		$index = $funcDef->getVarIndex($this->name);
		if ($index and $funcDef->getVar($index)->isConst()) {
			throw new ParseException(ParseException::DUPLICATE_NAME, $this->name);
		}
	}

	public function postReport($nonTerminal) {
		if ($nonTerminal <> 'constant-value') return;

		$parser = $this->parser;
		$value = $parser->getLastConstant();
		if ($this->isConst) $value->setIsConst();
		$this->addVar($this->name, $value);
	}

	public function finish() {
		if ($this->name) $this->addVar($this->name);
	}
}
