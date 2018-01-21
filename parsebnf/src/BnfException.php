<?php

class BnfException extends RuntimeException {
	const ERR_EOF = 1;
	const ERR_EMPTY = 2;
	const ERR_CHAR = 3;
	const ERR_QUOTE = 4;
	const ERR_TOKEN = 5;
	const ERR_DEFINED = 6;
	const ERR_UNDECIDABLE = 7;
	const ERR_MISSING = 8;
	const ERR_NOTERM = 9;

	protected static $messages = [
		self::ERR_CHAR => 'unexpected character: %s',
		self::ERR_DEFINED => 'nonterminal "%s" already defined',
		self::ERR_EMPTY => 'no definitions',
		self::ERR_EOF => 'unexpected end of file',
		self::ERR_MISSING => 'missing definitions for: %s',
		self::ERR_NOTERM => 'non-resolved dependencies: %s',
		self::ERR_QUOTE => 'missing closing quote',
		self::ERR_TOKEN => 'unexpected token: %s, %s expected',
		self::ERR_UNDECIDABLE => 'undecidable grammar for "%s": multiple choices for "%s"',
	];

	protected $data = [];
	protected $line;
	protected $col;

	public function getData() {
		return $this->data;
	}

	public function __construct($code, $data = [], $line = null, $col = null) {
		$data = (array)$data;
		$this->code = $code;
		$this->data = $data;
		$this->line = $line;
		$this->col = $col;
		$this->message = 'error ' . $code . ': ' . static::$messages[$code];
		if ($data) {
			foreach ($data as &$p) {
				if (is_array($p)) $p = implode(', ', $p);
			}
			$this->message = vsprintf($this->message, $data);
		}
		if ($col) $this->message .= " @ line $line col $col";
	}
}
