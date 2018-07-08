<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\ExpressionDef;

class MetaHandler extends AbstractStateHandler {
	protected $func;

	protected function init() {
		$env = $this->parser->getMetaEnv();
		if (!$env) throw new \RunException('отсутствует среда для метапрограмм');

		$this->func = $env->getMetaFunction();
		$this->parser->insertFunction($this->func);
	}

	public function finish() {
		$this->parser->endFunction();
		$this->parser->getMetaEnv()->runMetaFunction($this->func);
	}
}
