<?php

namespace ava12\tpl;

class Util {
	const ENCODING = 'utf8';

	/**
	 * @param int|float|string|bool $a
	 * @param int|float|string|bool $b
	 * @param bool $strict
	 * @return bool
	 */
	public static function compareScalars($a, $b, $strict = false) {
		if ($strict) return ($a === $b);

		if ($a === '0' and is_bool($b) or $b === '0' and is_bool($a)) {
			return (strlen($a) and strlen($b));
		} else {
			return ($a == $b);
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
		$headMask = 0x1f;
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

		return $result | (($prefix & ($mask - 1)) << ($i * 6));
	}

	public static function substr($text, $start, $length = null) {
		if (!isset($length)) $length = mb_strlen($text, static::ENCODING);
		return mb_substr($text, $start, $length, static::ENCODING);
	}
}
