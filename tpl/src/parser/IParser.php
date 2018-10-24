<?php

namespace ava12\tpl\parser;

use \ava12\tpl\machine\FunctionDef;
use \ava12\tpl\machine\Machine;
use \ava12\tpl\machine\CodeChunk;
use \ava12\tpl\machine\Variable;

interface IParser {

	/** @return Machine */
	public function getMachine();
	/** @return FunctionDef */
	public function getFunctionDef();
	/** @return Variable|null */
	public function getLastConstant();
	/** @param Variable|null */
	public function setLastConstant($var);

	public function setStringHandler($stringType, $handler);
	public function getStringHandler($stringType);

	public function setMetaEnv(IMetaEnv $env);
	/** @return IMetaEnv|null */
	public function getMetaEnv();

	public function pushSource($source, $name, $unique = false);
	public function pushSourceFile($name, $unique = false);

	public function insertFunction(FunctionDef $functionDef);
	public function beginFunction($isPure = false);
	public function endFunction();

	public function insertCodeChunk(CodeChunk $codeChunk);
	public function beginCodeChunk($type = CodeChunk::TYPE_MISC);
	public function endCodeChunk();

	/**
	 * @param string $op
	 * @param Token|null $token
	 */
	public function emit($op, $token = null);
	/**
	 * @param Token|null $token
	 */
	public function emitNull($token = null);
	/**
	 * @param bool $value
	 * @param Token|null $token
	 */
	public function emitBool($value, $token = null);
	/**
	 * @param int|float $value
	 * @param Token|null $token
	 */
	public function emitNumber($value, $token = null);
	/**
	 * @param int|float $value
	 * @param Token|null $token
	 */
	public function emitString($value, $token = null);
	/**
	 * @param string $op
	 * @param array|int|string|null $params
	 * @param Token|null $token
	 */
	public function emitOp($op, $params = null, $token = null);
	/**
	 * @param string $op
	 * @param string $chunkType
	 * @param Token|null $token
	 */
	public function emitOpChunk($op, $chunkType = self::CHUNK_TYPE_MISC, $token = null);

	const CHUNK_TYPE_MISC = CodeChunk::TYPE_MISC;
	const CHUNK_TYPE_LOOP = CodeChunk::TYPE_LOOP;
	const CHUNK_TYPE_DO = CodeChunk::TYPE_DO;
	const CHUNK_TYPE_CASE = CodeChunk::TYPE_CASE;

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
}
