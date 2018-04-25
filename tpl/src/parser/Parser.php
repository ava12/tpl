<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\AbstractFunctionDef;
use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\CodeChunk;
use \ava12\tpl\Util;

class Parser {
	const OP_TO_VALUE = 'toval';
	const OP_TO_SCALAR = 'toscalar';
	const OP_TO_NUMBER = 'tonum';
	const OP_TO_INT = 'toint';
	const OP_TO_STRING = 'tostr';
	const OP_TO_BOOL = 'tobool';
	const OP_FUNC = 'func';
	const OP_VAR = 'var';
	const OP_ITEM = 'item';
	const OP_MAKE_PAIR = 'pair';
	const OP_MAKE_LIST = 'mklist';
	const OP_MAKE_REF = 'mkref';
	const OP_DEREF = 'deref';
	const OP_SET = 'set';
	const OP_CONCAT = 'cat';
	const OP_CALL = 'call';
	const OP_DUP = 'dup';
	const OP_DROP = 'drop';
	const OP_SWAP = 'swap';
	const OP_IF = 'if';
	const OP_NOT_IF = 'nif';
	const OP_CASE = 'case';
	const OP_WHEN = 'when';
	const OP_DO = 'do';
	const OP_FOR = 'for';
	const OP_FOREACH = 'each';
	const OP_CONTINUE = 'continue';
	const OP_BREAK = 'break';
	const OP_WHILE = 'while';
	const OP_UNTIL = 'until';
	const OP_EXIT = 'exit';
	const OP_RETURN = 'return';
	const OP_DEFAULT = 'default';

	protected static $keywords = [
		'and',
		'break',
		'case',
		'continue',
		'define',
		'do',
		'else',
		'exit',
		'false',
		'for',
		'foreach',
		'function',
		'if',
		'or',
		'pure',
		'return',
		'then',
		'true',
		'var',
		'until',
		'when',
		'while',
	];

	const CHUNK_TYPE_MISC = CodeChunk::TYPE_MISC;
	const CHUNK_TYPE_LOOP = CodeChunk::TYPE_LOOP;
	const CHUNK_TYPE_DO = CodeChunk::TYPE_DO;
	const CHUNK_TYPE_CASE = CodeChunk::TYPE_CASE;

	protected $stringTypes = [
		Token::TYPE_STRING_SINGLE,
		Token::TYPE_STRING_DOUBLE,
		Token::TYPE_STRING_PERCENT,
	];

	public $machine;

	/** @var Lexer */
	protected $lexer;
	/** @var Lexer[] */
	protected $lexerStack = [];
	/** @var \ava12\tpl\machine\FunctionDef */
	public $functionDef;
	/** @var \ava12\tpl\machine\FunctionDef[]  */
	public $functionDefStack = [];
	/** @var CodeChunk */
	public $codeChunk;
	/** @var CodeChunk[] */
	public $codeChunkStack = [];

	/** @var null|Token */
	protected $savedToken = null;
	/** @var null|Token */
	protected $lastToken;
	/** @var null|Token */
	protected $prevToken;

	/** @var IStringProcessor[] */
	protected $stringHandlers = [];

	// {нетерминал => [{лексема => номер_состояния|false|[номер_состояния|false, нетерминал]}+]}
	protected $grammar;

	protected $currentNonTerminal = 0;
	protected $currentStateIndex = 0;
	/** @var null|AbstractStateHandler */
	protected $nonTerminalHandler = null;
	protected $currentState;
	/** @var AbstractStateHandler[] */
	protected $nonTerminalStack = []; // [[нетерминал, номер_состояния, обработчик]*]

	/** @var null|\ava12\tpl\machine\Variable */
	public $lastConstant;

	// {нетерминал => имя_класса}
	protected $nonTerminalHandlers = [
		'and-value' => 'LogicHandler',
		'arg-definition' => 'ArgHandler',
		'bool' => 'ScalarHandler',
		'call' => 'ListHandler',
		'case-block' => 'CaseHandler',
		'compound-expression' => 'WrapHandler',
		'compound-value' => 'WrapHandler',
		'const-compound' => 'ConstValueHandler',
		'const-object' => 'ConstValueHandler',
		'constant' => 'VarDefHandler',
		'deref' => 'WrapHandler',
		'deref-expression' => 'WrapHandler',
		'do-block' => 'WrapHandler',
		'field-body' => 'WrapHandler',
		'flow-control' => 'ControlHandler',
		'for-block' => 'ForHandler',
		'foreach-block' => 'ForeachHandler',
		'function' => 'FunctionHandler',
		'if-block' => 'IfHandler',
		'item-body' => 'ItemHandler',
		'key' => 'ScalarHandler',
		'list' => 'ListHandler',
		'literal-expression' => 'ObjectHandler',
		'loop-control' => 'ControlHandler',
		'loop-if-block' => 'IfHandler',
		'meta' => 'MetaHandler',
		'name' => 'NameHandler',
		'number' => 'ScalarHandler',
		'object-expression' => 'ObjectHandler',
		'or-value' => 'LogicHandler',
		'pair' => 'WrapHandler',
		'reference' => 'WrapHandler',
		'reference-expression' => 'WrapHandler',
		'string' => 'ScalarHandler',
		'variable-spec' => 'VarDefHandler',
		'when-case' => 'WhenHandler',
	];


	public function __construct(Machine $machine) {
		$this->grammar = require(__DIR__ . '/grammar.php');
		$this->currentState = $this->grammar[0][0];
		$this->machine = $machine;
		$this->functionDef = $this->machine->getFunction();
		$this->codeChunk = $this->functionDef->getCodeChunk();

		if (isset($this->nonTerminalHandlers[0])) {
			$className = $this->nonTerminalHandlers[0];
			$this->nonTerminalHandler = new $className($this, 0);
		}
	}

	public function setStringHandler($stringType, $handler) {
		$this->stringHandlers[$stringType] = $handler;
	}

	public function getStringHandler($stringType) {
		return (isset($this->stringHandlers[$stringType]) ? $this->stringHandlers[$stringType] : null);
	}

	protected function processStringToken($token) {
		$result = $token->value;
		if (isset($this->stringHandlers[$token->type])) {
			$result = $this->stringHandlers[$token->type]->process($result, $token->type);
		}
		return $result;
	}

	public function pushSource($source, $name, $unique = false) {
		if (!Util::checkEncoding($source)) {
			throw new ParseException(ParseException::WRONG_ENCODING, $name);
		}

		$sourceId = $this->machine->addSourceName($name, $unique);
		if (!$sourceId) return false;

		$this->lexerStack[] = $this->lexer;
		$this->lexer = new Lexer($source, $sourceId, $name);
		return true;
	}

	public function pushSourceFile($name, $unique = false) {
		$source = file_get_contents($name);
		if ($source === false) return false;
		else return $this->pushSource($source, $name, $unique);
	}

	public function insertFunction(AbstractFunctionDef $functionDef) {
		$this->functionDefStack[] = $this->functionDef;
		$this->functionDef = $functionDef;
		$this->codeChunkStack[] = $this->codeChunk;
		$this->codeChunk = $functionDef->addCodeChunk();
		return $functionDef;
	}

	public function beginFunction($isPure = false) {
		$parentDef = $this->functionDef;
		$index = count($this->functionDefStack);
		while (!isset($parentDef->index)) {
			$index--;
			$parentDef = $this->functionDefStack[$index];
		}
		$functionDef = $this->machine->addFunction($parentDef, $isPure);
		return $this->insertFunction($functionDef);
	}

	public function endFunction() {
		$this->functionDef = array_pop($this->functionDefStack);
		$this->codeChunk = array_pop($this->codeChunkStack);
	}

	public function insertCodeChunk($codeChunk) {
		$this->codeChunkStack[] = $this->codeChunk;
		$this->codeChunk = $codeChunk;
	}

	public function beginCodeChunk($type = CodeChunk::TYPE_MISC) {
		$codeChunk = $this->functionDef->addCodeChunk($type);
		$this->insertCodeChunk($codeChunk);
		return $codeChunk;
	}

	public function endCodeChunk() {
		$this->codeChunk = array_pop($this->codeChunkStack);
	}


	public function parse() {
		if (!$this->lexer) return;

		$this->functionDef = $this->machine->getFunction(0);
		$this->insertCodeChunk($this->functionDef->getCodeChunk());
		$currentState = &$this->currentState;

		$token = null;

		try {
			while (true) {
				$token = $this->nextToken();
				if (!$token or !$token->type) break;

				$tokenType = $token->type;
				$matched = isset($currentState[$tokenType]);
				if (!$matched and !isset($currentState[''])) {
					if ($tokenType <> Token::TYPE_LMETA) {
						$params = [Token::getName($tokenType), Token::getName(array_keys($currentState)[0])];
						throw new ParseException(ParseException::UNEXPECTED, $params, $token);
					}

					$this->pushState('meta', $this->currentStateIndex);
					$this->putToken($token);
					continue;
				}

				$entry = (array)$currentState[$matched ? $tokenType : ''];
				$nextState = $entry[0];
				$nonTerminal = (isset($entry[1]) ? $entry[1] : null);

				if ($matched and !isset($nonTerminal)) {
					$this->useToken($token);
				} else {
					$this->putToken($token);
				}

				if (isset($nonTerminal)) {
					$this->pushState($nonTerminal, $nextState);
					continue;
				}

				if ($nextState === false) {
					if ($this->nonTerminalStack) {
						$this->popState();
					} elseif ($tokenType == Token::TYPE_LMETA) {
						$this->pushState('meta', $this->currentStateIndex);
					} else {
						$params = [Token::getName($tokenType), Token::getName(array_keys($currentState)[0])];
						throw new ParseException(ParseException::UNEXPECTED, $params, $token);
					}

				} else {
					$this->currentStateIndex = $nextState;
					$currentState = $this->grammar[$this->currentNonTerminal][$nextState];
				}
			}

			while ($this->nonTerminalStack) {
				if (isset($this->currentState['']) and $this->currentState[''] === false) {
					$this->popState();
				} else {
					throw new ParseException(ParseException::UNEXPECTED_EOF, null, $token);
				}
			}
		} catch (ParseException $e) {
			if (!$e->getToken()) {
				$e = new ParseException($e->getCode(), $e->getData(), $this->lastToken);
			}
			throw $e;
		}

		$this->endCodeChunk();
	}

	/**
	 * @return Token|null
	 */
	protected function nextToken() {
		if ($this->savedToken) {
			$result = $this->savedToken;
			$this->savedToken = null;
			$this->lastToken = $result;
			return $result;
		}

		$result = null;
		while ($this->lexer) {
			$result = $this->lexer->next();
			if ($result->type) break;

			$this->lexer = array_pop($this->lexerStack);
		}

		if (
			$result->type == Token::TYPE_NAME and
			in_array($result->value, static::$keywords) and
			(!$this->lastToken or $this->lastToken->type <> Token::TYPE_DOT))
		{
			$result->type = $result->value;
		}

		if (in_array($result->type, $this->stringTypes)) {
			$result->value = $this->processStringToken($result);
		}

		$this->prevToken = $this->lastToken;
		$this->lastToken = $result;
		return $result;
	}

	protected function putToken($token) {
		$this->savedToken = $token;
		$this->lastToken = $this->prevToken;
	}

	protected function pushState($nonTerminal, $stateIndex) {
		$this->nonTerminalStack[] = [$this->currentNonTerminal, $stateIndex, $this->nonTerminalHandler];
		$this->currentNonTerminal = $nonTerminal;
		$this->nonTerminalHandler = null;
		$this->currentStateIndex = 0;
		$this->currentState = $this->grammar[$nonTerminal][0];
		if (isset($this->nonTerminalHandlers[$nonTerminal])) {
			$className = __NAMESPACE__ . '\\' . $this->nonTerminalHandlers[$nonTerminal];
			$this->nonTerminalHandler = new $className($this, $nonTerminal);
		}

		$handler = $this->nonTerminalHandler;
		$index = count($this->nonTerminalStack);
		while ($index > 0 and !$handler) {
			$index--;
			$handler = $this->nonTerminalStack[$index][2];
		}
		if ($handler) $handler->preReport($nonTerminal);
	}

	protected function popState() {
		do {
			if ($this->nonTerminalHandler) {
				$this->nonTerminalHandler->finish();
			}
			$savedState = array_pop($this->nonTerminalStack);

			$this->currentNonTerminal = $savedState[0];
			$stateIndex = $savedState[1];
			$this->nonTerminalHandler = $savedState[2];
			$handler = $this->nonTerminalHandler;
			$index = count($this->nonTerminalStack);
			while ($index > 0 and !$handler) {
				$index--;
				$handler = $this->nonTerminalStack[$index][2];
			}
			if ($handler) $handler->postReport($this->currentNonTerminal);

		} while ($stateIndex === false);

		$this->currentStateIndex = $stateIndex;
		$this->currentState = $this->grammar[$this->currentNonTerminal][$stateIndex];
	}

	protected function useToken($token) {
		$index = count($this->nonTerminalStack) - 1;
		$handler = $this->nonTerminalHandler;
		while (!$handler and $index >= 0) {
			$handler = $this->nonTerminalStack[$index][2];
			$index--;
		}

		if ($handler) $handler->useToken($token);
	}

	public function emit($op, $token = null) {
		if (!isset($token)) $token = $this->lastToken;
		$this->codeChunk->emit($op, $token->sourceId, $token->line);
	}

	public function emitNull($token = null) {
		$this->emit('', $token);
	}

	public function emitBool($value, $token = null) {
		$this->emit((bool)$value, $token);
	}

	public function emitNumber($value, $token = null) {
		$this->emit(0 + $value, $token);
	}

	public function emitString($value, $token = null) {
		$this->emit('$' . $value, $token);
	}

	public function emitOp($op, $params = null, $token = null) {
		if (isset($params)) {
			$params = (array)$params;
			array_unshift($params, $op);
			$op = $params;
		}
		$this->emit($op, $token);
	}

	public function emitOpChunk($op, $chunkType = self::CHUNK_TYPE_MISC, $token = null) {
		$chunk = $this->functionDef->addCodeChunk($chunkType);
		$this->emitOp($op, $chunk->index, $token);
		$this->insertCodeChunk($chunk);
	}
}
