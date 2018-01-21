<?php

require_once __DIR__ . '/src/BnfParser.php';

const FORMAT_PHP = 'php';
const FORMAT_JSON = 'json';
const NL = PHP_EOL;


$outputFormat = FORMAT_PHP;
$formatJson = false;
$includedPhp = false;
$fileName = null;
$output = STDERR;

$source = '';

/*
нетерминал => [{терминал => [индекс_состояния, нетерминал]|индекс_состояния|false}]
false: последний терминал - завершить обработку
специальные "терминалы":
 "": "любой другой" - перейти к следующему состоянию (либо завершить)
 "=нетерминал": - заменить на первые терминалы
*/
$grammar = [];


function report($message) {
	global $output;

	fwrite($output, $message . NL);
}

function reportError($message) {
	report('  Error: ' . $message);
}

function printHelp() {
	report('Usage is php parsebnf.php [-p | -P | -j | -J] [-e] <bnf_file>');
	report('  -e  write errors to STDOUT instead of STDIN');
	report('  -j  output grammar as compact JSON');
	report('  -J  output grammar as formatted JSON');
	report('  -p  output grammar in PHP format (default)');
	report('  -P  output included PHP file');
	exit(1);
}

function parseArgs() {
	global $fileName, $outputFormat, $includedPhp, $formatJson, $output;

	$args = $_SERVER['argv'];
	array_shift($args);

	if (!$args) printHelp();

	while ($args) {
		$arg = array_shift($args);

		if (substr($arg, 0, 1) <> '-') {
			$fileName = $arg;
			break;
		}

		$arg = str_split($arg);
		array_shift($arg);
		foreach ($arg as $key) {
			switch ($key) {
				case 'e':
					$output = STDOUT;
				break;

				case 'P':
					$includedPhp = true;
				case 'p':
				break;

				case 'J':
					$formatJson = true;
				case 'j':
					$outputFormat = FORMAT_JSON;
				break;

				default:
					printHelp();
			}
		}
	}
}

function quote($value) {
	return json_encode($value, JSON_UNESCAPED_UNICODE);
}

function dumpJson($grammar) {
	$eol = NL;
	foreach ($grammar as $nonTerminal => $states) {
		foreach ($states as $index => $state) {
			foreach ($state as $token => $rule) {
				$state[$token] = '      ' . quote((string)$token) . ': ' . quote($rule);
			}
			$state = implode(",$eol", $state);
			$states[$index] = "    {{$eol}$state$eol    }";
		}
		$states = implode(",$eol", $states);
		$key = quote((string)$nonTerminal);
		$grammar[$nonTerminal] = "  $key: [$eol$states$eol  ]";
	}
	$grammar = implode(",$eol", $grammar);
	echo "{{$eol}$grammar$eol}$eol";
}

function dumpPhp($grammar) {
	echo '[' . NL;

	foreach ($grammar as $nonTerminal => $states) {
		echo "  '$nonTerminal' => [" . NL;
			foreach ($states as $index => $state) {
				echo "    $index => [" . NL;

				foreach ($state as $terminal => $newState) {
					$terminal = addcslashes($terminal, '\\\'');
					if (is_array($newState)) {
						$newIndex = ($newState[0] === false ? 'false' : $newState[0]);
						$newState = "[{$newIndex},'" . addcslashes($newState[1], '\\\'') . '\']';
					} elseif ($newState === false) {
						$newState = 'false';
					}
					echo "      '$terminal' => $newState," . NL;
				}

				echo '    ],' . NL;
			}
		echo '  ],' . NL;
	}

	echo ']';
}

function dumpGrammar() {
	global $grammar, $outputFormat, $includedPhp, $formatJson;

	switch ($outputFormat) {
		case FORMAT_JSON:
			if ($formatJson) dumpJson($grammar);
			else echo json_encode($grammar, JSON_UNESCAPED_UNICODE);
		break;

		case FORMAT_PHP:
			if ($includedPhp) echo '<?php' . NL . NL . 'return ';
			//var_export($grammar);
			dumpPhp($grammar);
			echo ';' . NL;
		break;
	}

	echo NL;
}

//--------------------------------------------------------------------------

try {
	parseArgs();
	if (!is_readable($fileName) or is_dir($fileName)) {
		reportError('no source file');
		exit(1);
	}

	$source = file_get_contents($fileName);
	$parser = new BnfParser($source);
	$grammar = $parser->parse();
	dumpGrammar();

} catch (Exception $e) {
	reportError($e);
	exit(1);
}
