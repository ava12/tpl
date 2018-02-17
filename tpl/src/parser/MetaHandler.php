<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\ExpressionDef;

class MetaHandler extends AbstractStateHandler {
	protected $func;

	protected function init() {
		$this->func = new ExpressionDef();
		$macroProcessor = $this->parser->getStringHandler(Token::TYPE_STRING_PERCENT);
		if ($macroProcessor) $macroProcessor->initMetaFunction($this->func);
		$this->parser->insertFunction($this->func);
	}

	public function finish() {
		$this->parser->endFunction();
		$this->parser->machine->computeExpression($this->func);
	}
}
