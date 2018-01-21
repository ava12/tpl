<?php

namespace ava12\tpl\parser;

class ParseException extends \ava12\tpl\AbstractException {
	const UNEXPECTED_EOF = 101;
	const WRONG_CHAR = 102;
	const NO_QUOTE = 103;
	const UNEXPECTED = 104;
	const DUPLICATE_NAME = 105;
	const NO_NAME = 106;
	const IMPURE_DEF = 107;
	const IMPURE_SIDE = 108;
	const IMPURE_SUB = 109;

	protected static $messages = [
		self::DUPLICATE_NAME => 'имя "%s" уже определено',
		self::IMPURE_DEF => 'чистая функция не может обращаться к неконстантным объектам',
		self::IMPURE_SIDE => 'чистая функция не может иметь побочные эффекты',
		self::IMPURE_SUB => 'функции, объявленные в чистой функции, должны быть чистыми',
		self::NO_NAME => 'имя "%s" не определено в данном контексте',
		self::NO_QUOTE => 'отсутствует закрывающая кавычка',
		self::UNEXPECTED => 'неожиданная лексема %s',
		self::UNEXPECTED_EOF => 'неожиданный конец файла',
		self::WRONG_CHAR => 'некорректный символ: %s',
	];

	protected $token;

	public function getToken() {
		return $this->token;
	}

	/**
	 * @param string $code
	 * @param array|string|int|null $data
	 * @param Token|null $token
	 */
	public function __construct($code, $data = null, $token = null) {
		$this->init($code, $data);
		$this->token = $token;
		if ($token) {
			$this->message .= ', ' . $token->sourceName . ' @ ' . $token->line;
			if ($token->column) $this->message .= ':' . $token->column;
		}
	}

	public function getSourceId() { return $this->token->sourceId; }
	public function getSourceName() { return $this->token->sourceName; }
	public function getSourceLine() { return $this->token->line; }
	public function getSourceColumn() { return $this->token->column; }
}
