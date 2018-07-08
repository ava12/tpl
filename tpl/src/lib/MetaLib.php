<?php

namespace ava12\tpl\lib;

use \ava12\tpl\parser\IMetaEnv;
use \ava12\tpl\machine\ExpressionDef;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\Machine;
use \ava12\tpl\parser\Parser;
use \ava12\tpl\parser\ParseException;
use \ava12\tpl\parser\Token;
use \ava12\tpl\env\Env;


class MetaLib implements ILib, IMetaEnv {
	protected static $funcs = [
		'macros' => ['callMacros', 1],
	];


	/** @var Machine */
	protected $machine;
	/** @var Parser */
	protected $parser;

	protected $macros = [];
	protected static $macroRe = '/\\\\([^\\\\]*)\\\\/';


	public function __construct(Machine $machine, Parser $parser) {
		$this->machine = $machine;
		$this->parser = $parser;
		$parser->setStringHandler(Token::TYPE_STRING_PERCENT, [$this, 'processString']);
		$parser->setMetaEnv($this);
	}

	public function processString($text, $stringType) {
		return preg_replace_callback(static::$macroRe, [$this, 'replaceCallback'], $text);
	}

	public function replaceCallback($match) {
		if (!strlen($match[1])) return '\\';

		$key = $match[1];
		if (!isset($this->macros[$key])) {
			throw new ParseException(ParseException::NO_MACRO, $key);
		}

		return $this->macros[$key];
	}

	public static function setup(Env $env) {
		$machine = $env->machine;
		$parser = $env->parser;
		$instance = new static($machine, $parser);
		return $instance;
	}

	public function callMacros($args) {
		/** @var Variable[] $args */
		$macros = $this->machine->toList($args[0])->getValue();
		for ($i = 1; $i <= $macros->getCount(); $i++) {
			$key = $macros->getKeyByIndex($i);
			if (isset($key)) {
				$value = $this->machine->toString($macros->getByIndex($i));
				$this->macros[$key] = $value;
			}
		}
		return null;
	}

	public function getMetaFunction() {
		$result = new ExpressionDef;
		foreach (static::$funcs as $name => $def) {
			$func = new FunctionProxy([$this, $def[0]], $def[1]);
			$result->addVar($name, new Variable($func, true));
		}
		return $result;
	}

	public function runMetaFunction(ExpressionDef $func) {
		$this->machine->computeExpression($func, '-meta-');
	}
}
