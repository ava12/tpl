<?php

namespace ava12\tpl\parser;

use \ava12\tpl\Util;

class Lexer {
	public $source;
	public $sourceId;
	public $sourceName;
	protected $currentPos = 0;
	protected $lineEndPos = [0];
	protected $currentLine = 0;
	protected $currentColumn = 0;

	const COMMENT_LINE = '\\\\';
	const COMMENT_BLOCK = '\\*';
	const COMMENT_BLOCK_END = '*\\';

	protected $tokenRe;

	const MATCH_NUMBER = 1;
	const MATCH_NAME = 2;
	const MATCH_STRING = 3;
	const MATCH_OP = 4;
	const MATCH_WRONG = 5;
	const MATCH_MAX = 5;

	protected $matchFunc = [
		self::MATCH_NUMBER => 'emitNumber',
		self::MATCH_NAME => 'emitName',
		self::MATCH_STRING => 'emitString',
		self::MATCH_OP => 'emitOp',
		self::MATCH_WRONG => 'emitWrong',
	];

	protected $stringRe = '/([\'"%]).*?\\1(\\1.*?\\1)*/s';
	protected $opMap = [
		'(:' => '[', ':)' => ']',
		'((.' => '{', '.))' => '}',
		'(::' => '[[', '::)' => ']]',
	];


	public function __construct($source, $sourceId, $sourceName) {
		$this->tokenRe =
				'/\\\\\\\\|\\\\\\*' .
				'|(0[xX][0-9a-fA-F]{1,8}|-?[0-9]+(?:\\.[0-9]+)?(?:[eE][+-]?[0-9]+)?)' .
				"|([^\\s:\\.@\$(){}\\[\\]'\"`%,\\0-\x08\x0b\x0c\x0e-\x1f\x7f-\xc2\x9f]+)" .
				'|([\'"%])' .
				'|(\\[\\[|\\]\\]|\\(::?|:\\)|::\\)?|\\(\\(\\.|\\.\\)\\)|[(){}\\[\\]:.@$,])' .
				"|([`\\0-\x08\x0b\x0c\x0e-\x1f\x7f-\xc2\x9f\xef\xbf\xbe\xef\xbf\xbf])" .
				'/u';

		$this->source = $source;
		$this->sourceId = $sourceId;
		$this->sourceName = $sourceName;
		preg_match_all('/\\r\\n|\\r|\\n/', $this->source, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $match) {
			$this->lineEndPos[] = $match[1] + strlen($match[0]);
		}
		$this->lineEndPos[] = strlen($source);
	}

	public function next() {
		while ($this->currentPos < strlen($this->source)) {
			if (!preg_match($this->tokenRe, $this->source, $matches, PREG_OFFSET_CAPTURE, $this->currentPos)) {
				$this->currentPos = strlen($this->source);
				break;
			}

			for ($i = array_keys($matches)[count($matches) - 1]; $i > 0; $i--) {
				if (strlen($matches[$i][0])) {
					$this->currentPos = $matches[$i][1] + strlen($matches[$i][0]);
					$this->getLineColumn();
					$funcName = $this->matchFunc[$i];
					return $this->$funcName($matches[$i][0]);
				}
			}

			$this->currentPos = $matches[0][1] + strlen($matches[0][0]);
			$this->skipComment($matches[0][0]);
		}

		return $this->emit(Token::TYPE_EOF, null);
	}

	protected function getLineColumn() {
		$currentPos = $this->currentPos;
		$lineCnt = count($this->lineEndPos);
		while ($this->currentLine < $lineCnt and $this->lineEndPos[$this->currentLine] <= $currentPos) {
			$this->currentLine++;
		}
		$this->currentLine--;
		$linePos = $this->lineEndPos[$this->currentLine];
		$lineHead = substr($this->source, $linePos, $currentPos - $linePos);
		$this->currentColumn = mb_strlen($lineHead);
	}

	protected function skipComment($commentType) {
		if ($commentType == self::COMMENT_BLOCK) {
			$this->currentPos = strpos($this->source, self::COMMENT_BLOCK_END, $this->currentPos);
			if ($this->currentPos) {
				$this->currentPos += strlen(self::COMMENT_BLOCK_END);
			} else {
				$this->currentPos = strlen($this->source);
			}
		} else {
			if (preg_match('/\\r\\n|[\\r\\n]/', $this->source, $matches, PREG_OFFSET_CAPTURE, $this->currentPos)) {
				$this->currentPos = $matches[0][1] + strlen($matches[0][0]);
			} else {
				$this->currentPos = strlen($this->source);
			}
		}
	}

	protected function emit($type, $value) {
		return new Token($type, $value, $this->sourceId, $this->sourceName, $this->currentLine + 1, $this->currentColumn + 1);
	}

	protected function getFakeToken() {
		return $this->emit(null, null);
	}

	protected function emitNumber($value) {
		$value = strtolower($value);
		if (substr($value, 0, 2) == '0x') $value = hexdec($value);
		else $value += 0;
		return $this->emit(Token::TYPE_NUMBER, $value);
	}

	protected function emitString($quote) {
		if (!preg_match($this->stringRe, $this->source, $matches, 0, $this->currentPos - 1)) {
			throw new ParseException(ParseException::NO_QUOTE, null, $this->getFakeToken());
		}

		$value = str_replace($quote . $quote, $quote, substr($matches[0], 1, -1));
		$this->currentPos += strlen($matches[0]) - 1;
		return $this->emit($quote, $value);
	}

	protected function emitOp($value) {
		if (isset($this->opMap[$value])) $value = $this->opMap[$value];
		return $this->emit($value, $value);
	}

	protected function emitName($value) {
		return $this->emit(Token::TYPE_NAME, $value);
	}

	protected function emitWrong($value) {
		$charCode = $value . ' (U+' . substr('000' . dechex(Util::ord($value)), -4) . ')';
		$token = $this->emit($value, $value);
		throw new ParseException(ParseException::WRONG_CHAR, $charCode, $token);
	}
}
