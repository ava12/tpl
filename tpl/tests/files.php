<?php

return [
  'ro' => ['r', [
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
	'ac' => ['rac', [
		'log.txt' => "123\r\n",
	]],
	'cwn' => ['rcwn', [
		'sub' => [],
		'foo.txt' => '',
	]],
	'wd' => ['rwd', [
		'sub' => [],
		'foo.txt' => '',
		'bar.txt' => '',
	]],
];
