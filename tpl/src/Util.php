<?php

namespace ava12\tpl;

class Util {
	const ENCODING = 'utf8';
	const FLOAT_PRECISION = 14;
	const ROUND_ERROR = 1e-14;

	public static function normalizeFloat($f) {
		return round($f, static::FLOAT_PRECISION);
	}

	public static function compareFloats($a, $b) {
		$a = static::normalizeFloat($a);
		$b = static::normalizeFloat($b);
		if (abs($a - $b) < static::ROUND_ERROR) return 0;
		else return ($a > $b ? 1 : -1);
	}

	/**
	 * @param int|float|string|bool|null $a
	 * @param int|float|string|bool|null $b
	 * @param bool $strict
	 * @return bool true: равны
	 */
	public static function compareScalars($a, $b, $strict = false) {
		if (is_int($a)) $a = static::normalizeFloat((float)$a);
		if (is_int($b)) $b = static::normalizeFloat((float)$b);
		if ($strict) return ($a === $b);

		if (is_string($a)) $b = (string)$b;
		elseif (is_string($b)) $a = (string)$a;

		if (is_string($a)) {
			return ($a === $b);
		} elseif (is_float($a) and is_float($b)) {
			return !static::compareFloats($a, $b);
		} else {
			return ((int)$a == (int)$b);
		}
	}

	public static function checkEncoding($text) {
		return mb_check_encoding($text, static::ENCODING);
	}

	protected static function makeException($utf) {
		$chars = str_split(bin2hex($utf), 2);
		return new \RuntimeException('некорректная последовательность UTF-8: ' . $chars);
	}

	public static function chr($code) {
		if ($code < 128) return chr($code);

		$suffix = '';
		$prefix = 0x80;
		$headMask = 0x3f;
		while ($code > $headMask) {
			$suffix = chr(($code & 0x3f) | 0x80) . $suffix;
			$code >>= 6;
			$prefix = ($prefix >> 1) | 0x80;
			$headMask >>= 1;
		}

		return chr($prefix | $code) . $suffix;
	}

	public static function ord($char) {
		$chars = str_split(mb_substr($char, 0, 1, static::ENCODING));
		$prefix = ord($chars[0]);
		if ($prefix < 0x80) return $prefix;

		$result = 0;
		$mask = 0x40;
		for ($i = 1; $i < count($chars); $i++) {
			$byte = ord($chars[$i]);
			if (!($prefix & $mask) or ($byte & 0xc0) <> 0x80) {
				throw static::makeException($char);
			}

			$result = ($result << 6) | ($byte & 0x3f);
			$mask >>= 1;
		}

		if ($prefix & $mask) throw static::makeException($char);

		return $result | (($prefix & ($mask - 1)) << ((count($chars) - 1) * 6));
	}

	public static function substr($text, $start, $length = null) {
		if (!isset($length)) $length = mb_strlen($text, static::ENCODING);
		return mb_substr($text, $start, $length, static::ENCODING);
	}

	public static function strlen($text) {
		return mb_strlen($text, static::ENCODING);
	}

	public static function handleErrors() {
		set_error_handler(function ($level, $message, $file, $line) {
			throw new \ErrorException($message, $level, $level, $file, $line);
		});
	}
}
