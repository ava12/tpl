<?php

return [
  'ro' => ['', [
		'eols' => [
			'cr' => "hello\rworld",
			'crlf' => "hello\r\nworld",
			'lf' => "hello\nworld",
		],
		'parent' => [
			'child' => [
				'file' => '',
			],
		],
		'void' => '',
		'тест' => [
			'тест' => 'тест'
		],
		'.hidden' => '',
		'wrong-enc' => "\xc0\x81",
	]],
	'ac' => ['ac', [
		'log.txt' => "123\r\n",
	]],
	'cwn' => ['cwn', [
		'sub' => [],
		'foo.txt' => '',
	]],
	'wd' => ['wd', [
		'sub' => [],
		'foo.txt' => '',
		'bar.txt' => '',
	]],
];
