<?php

namespace ava12\tpl\lib;

use \ava12\tpl\parser\IMetaEnv;
use \ava12\tpl\machine\FunctionDef;
use \ava12\tpl\machine\Variable;
use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\RunException;
use \ava12\tpl\parser\Parser;
use \ava12\tpl\parser\ParseException;
use \ava12\tpl\parser\Token;
use \ava12\tpl\env\Env;


class MetaLib implements ILib, IMetaEnv {
	protected static $funcs = [
		'macros' => ['callMacros', 1],
		'include' => ['callInclude', 1],
		'require' => ['callRequire', 1],
	];

	const MAX_INCLUDES = 127;
	protected $incCnt = 0;

	/** @var Machine */
	protected $machine;
	/** @var Parser */
	protected $parser;
	/** @var FileSys */
	protected $fileSys;

	protected $macros = [];
	protected static $macroRe = '/\\\\([^\\\\]*)\\\\/';

	protected $includes = []; // [Path|":имя": Path]

	public function __construct(Machine $machine, Parser $parser, FileSys $fileSys) {
		$this->machine = $machine;
		$this->parser = $parser;
		$this->fileSys = $fileSys;
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
		$fileSys = $env->fileSys;
		$instance = new static($machine, $parser, $fileSys);
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

	protected function getIncludeInfo($name) {
		$path = $this->fileSys->getFileInfo($name);
		if ($path->error or !$path->exists or !($path->perm & FileSys::PERM_INCLUDE)) {
			throw new RunException(RunException::INC, $name);
		}
		return $path;
	}

	public function callInclude($args) {
		/** @var Variable[] $args */
		$name = $this->machine->toString($args[0]);
		$this->includes[] = $this->getIncludeInfo($name);
		return null;
	}

	public function callRequire($args) {
		/** @var Variable[] $args */
		$name = $this->machine->toString($args[0]);
		$this->includes[':' . $name] = $this->getIncludeInfo($name);
		return null;
	}

	public function getMetaFunction() {
		$result = $this->machine->makeExpression();
		foreach (static::$funcs as $name => $def) {
			$func = new FunctionProxy([$this, $def[0]], $def[1]);
			$result->addVar($name, new Variable($func, true));
		}
		return $result;
	}

	public function runMetaFunction(FunctionDef $func) {
		$this->includes = [];

		$this->machine->computeExpression($func, '-meta-');

		foreach (array_reverse($this->includes, true) as $k => $path) {
			$unique = (!is_numeric($k));
			$source = file_get_contents($path->realName);
			if ($this->parser->pushSource($source, $path->name, $unique)) {
				$this->incCnt++;
				if ($this->incCnt > static::MAX_INCLUDES) {
					throw new ParseException(ParseException::INCLUDE_DEPTH);
				}
			}
		}
	}
}
