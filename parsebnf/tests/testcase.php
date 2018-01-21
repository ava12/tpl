<?php

require_once __DIR__ . '/../src/BnfParser.php';

function reportError($message) {
	echo 'error: ' . $message . PHP_EOL;
	exit(1);
}

function checkResult(array $got, array $expected, $path = '') {
	foreach ($expected as $key => $value) {
		if (!array_key_exists($key, $got)) reportError("$path$key is missing");

		$gotValue = $got[$key];
		if (is_array($value)) {
			if (!is_array($gotValue)) reportError("$path$key: array expected, got \"$gotValue\"");

			if (!$value and $gotValue) reportError("$path$key: empty array expected");

			checkResult($gotValue, $value, $path . "$key.");

		} else {
			if (is_array($gotValue)) reportError("$path$key: \"$value\" expected, got array");

			if ($value !== $gotValue) {
				$value = gettype($value) . "($value)";
				$gotValue = gettype($gotValue) . "($gotValue)";
				reportError("$path$key: $value expected, got $gotValue");
			}
		}
	}
}

function checkError(BnfException $e, array $params) {
	// название => [код, обязательных_параметров?, массив?]
	$errors = [
		'char' => [BnfException::ERR_CHAR],
		'defined' => [BnfException::ERR_DEFINED, 1],
		'empty' => [BnfException::ERR_EMPTY],
		'eof' => [BnfException::ERR_EOF],
		'no-definition' => [BnfException::ERR_MISSING, 1, true],
		'no-terminal' => [BnfException::ERR_NOTERM, 1, true],
		'quote' => [BnfException::ERR_QUOTE],
		'token' => [BnfException::ERR_TOKEN, 1],
		'undecidable' => [BnfException::ERR_UNDECIDABLE, 2],
	];

	$error = array_shift($params);

	if (!isset($errors[$error])) {
		reportError('unknown error type: ' . $error);
	}

	$error = $errors[$error];
	if (!empty($error[1]) and count($params) < $error[1]) {
		reportError('missing required annotation parameter');
	}

	if ($e->getCode() <> $error[0]) {
		reportError('unexpected error type:' . PHP_EOL . $e->getMessage());
	}

	$data = $e->getData();
	if (empty($error[2])) {
		foreach ($params as $index=> $param) {
			if (!isset($data[$index]) or ($data[$index] <> $param)) {
				reportError($e->getMessage());
			}
		}
	} else {
		$data = $data[0];
		foreach ($params as $param) {
			if (!in_array($param, $data)) {
				reportError($e->getMessage());
			}
		}
	}
}


$sourceName = $_SERVER['argv'][1];
echo '  ' . basename($sourceName, '.bnf') . '...';
$source = file(__DIR__ . DIRECTORY_SEPARATOR . $sourceName);

$exception = null;
$parser = new BnfParser(implode('', $source));
$got = null;
try {
	$got = $parser->parse();
} catch (BnfException $e) {
	$exception = $e;
}

$directive = null;
foreach ($source as $index => $line) {
	if (!preg_match('/^\\s*#\\s*@([a-z-]+)(.*)/', $line, $matches)) continue;

	if ($matches[1] == 'result' or $matches[1] == 'error') {
		$directive = $matches[1];
		$params = trim($matches[2]);
		break;
	}
}

if (!$directive) reportError('@result or @error directive required');

if ($directive == 'result') {
	if ($exception) reportError('result expected, got error' . PHP_EOL . $exception);

	if (substr($params, 0, 1) <> ':') $expected = $params;
	else {
		$expected = '';
		for ($index++; $index < count($source); $index++) {
			if (preg_match('/^\\s*#(?:\\s*(\\S.*))?/', $source[$index], $matches)) {
				if (!empty($matches[1])) $expected .= trim($matches[1]);
			} else break;
		}
	}

	if ($expected) {
		$expected = json_decode($expected, true);
		if (!is_array($expected)) reportError('invalid result specification');

		checkResult($got, $expected);
	}
} elseif ($directive == 'error') {
	if (!$exception) reportError('error expected, got result');

	$params = preg_split('/\\s+/', preg_replace('/\\s+/', ' ', $params));
	checkError($exception, $params);
}

echo 'passed' . PHP_EOL;
