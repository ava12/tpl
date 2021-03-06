<?php

namespace ava12\tpl\parser;

class Token {
	public $type;
	public $value;
	public $name;
	public $sourceId;
	public $sourceName;
	public $line;
	public $column;

	const TYPE_EOF = null;
	const TYPE_NAME = '*';
	const TYPE_NUMBER = '1';
	const TYPE_DOT = '.';
	const TYPE_DDOT = '..';
	const TYPE_SET = ':';
	const TYPE_APPEND = '::';
	const TYPE_REF = '@';
	const TYPE_DEREF = '$';
	const TYPE_COMMA = ',';
	const TYPE_STRING_SINGLE = '\'';
	const TYPE_STRING_DOUBLE = '"';
	const TYPE_STRING_PERCENT = '%';
	const TYPE_LBRACE = '(';
	const TYPE_RBRACE = ')';
	const TYPE_LSQUARE = '[';
	const TYPE_RSQUARE = ']';
	const TYPE_LCURLY = '{';
	const TYPE_RCURLY = '}';
	const TYPE_LDOUBLE = '(:';
	const TYPE_RDOUBLE = ':)';
	const TYPE_TRUE = 'true';
	const TYPE_FALSE = 'false';
	const TYPE_LMETA = '\\{';
	const TYPE_RMETA = '}\\';


	public function __construct($type, $value, $sourceId, $sourceName, $line, $column) {
		$this->type = $type;
		$this->value = $value;
		$this->name = static::getName($type);
		$this->sourceId = $sourceId;
		$this->sourceName = $sourceName;
		$this->line = $line;
		$this->column = $column;
	}

	public static function getName($type) {
		switch ($type) {
			case self::TYPE_NUMBER:
				return ':число:';
			break;

			case self::TYPE_STRING_SINGLE:
			case self::TYPE_STRING_DOUBLE:
			case self::TYPE_STRING_PERCENT:
				return ':строка:';
			break;

			case self::TYPE_NAME:
				return ':имя:';
			break;

			default:
				return "\"$type\"";
		}
	}
}
