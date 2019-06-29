<?php

const ENCODING = 'UTF-8';

const ENV_INPUT_DIR = 'INPUT_DIR';
const ENV_INPUT_NAME = 'INPUT_NAME';

const CDL = 20;

const ERR_CANNOT_READ = 10;
$errCode = 0;

try {
	require_once(__DIR__ . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . 'autoload.php');

	init();
	$iniParser = new ava12\tpl\cli\IniParser;
	$cliConfig = new ava12\tpl\cli\Config;
	$argParser = new ava12\tpl\cli\ArgParser($cliConfig, $iniParser);
	$argParser->parse($argv);

	$iniParser->setEnv(ENV_INPUT_DIR, '');
	$iniParser->setEnv(ENV_INPUT_NAME, '');
	$cliConfig->apply($iniParser->getResult());

	$fsEnc = $iniParser->getResult('files.nameEncoding');

	foreach ($cliConfig->inputMask as $mask) {
		if (isset($fsEnc)) {
			$mask = mb_convert_encoding($mask, $fsEnc, ENCODING);
		}
		$names = glob($mask);
		foreach ((array)$names as $encodedName) {
			$encodedName = realpath($encodedName);
			$name = (isset($fsEnc) ? mb_convert_encoding($encodedName, ENCODING, $fsEnc) : $encodedName);
			if (!is_file($encodedName) or !is_readable($encodedName)) {
				report(ERR_CANNOT_READ, "невозможно прочитать файл $name");
				continue;
			}

			if (DIRECTORY_SEPARATOR <> '/') {
				$name = str_replace(DIRECTORY_SEPARATOR, '/', $name);
			}
			$iniParser->setEnv(ENV_INPUT_DIR, dirname($name));
			$iniParser->setEnv(ENV_INPUT_NAME, basename($name));

			$source = file_get_contents($encodedName);
			$source = preg_replace('/\\r\\n?/', "\n", $source);

			if ($cliConfig->testMode) {
				runTest($name, $source);
			} else {
				runGen($name, $source);
			}
		}
	}

} catch (\ava12\tpl\cli\ArgException $e) {
	writeln($e->getMessage());
	exit($e->getCode());

} catch (\ava12\tpl\machine\RunException $e) {
	write(PHP_EOL . $e->getMessage() . PHP_EOL . $e->formatDebugEntry());

	$de = $e->getDebugEntry();
	$errorIp = $de->chunkIp;
	$ip = max(0, $errorIp - CDL + 3);
	if (isset($de->funcDef)) {
		$code = array_slice($de->funcDef->getCodeChunk($de->chunkIndex)->code, $ip, CDL, true);
		foreach ($code as $ip => &$p) {
			if (is_array($p)) $p = '[' . implode(',', $p) . ']';
			if ($ip == $errorIp) $p = '>' . $p . '<';
		}
		write('код: ' . implode(', ', $code) . PHP_EOL);
	}
	exit(1);

} catch (\ava12\tpl\parser\ParseException $e) {
	write(PHP_EOL . $e->getMessage() . PHP_EOL);
	exit(1);

} catch (\Exception $e) {
	writeln($e->getMessage());
	exit(1);
}

exit(0);


function write($text) {
	global $cliConfig;

	if (is_array($text)) {
		$text = implode(PHP_EOL, $text);
	}
	fwrite(STDERR, $cliConfig->encodeCon($text));
}

function writeln($text) {
	if (is_array($text)) $text = implode(PHP_EOL, $text);
	write($text . PHP_EOL);
}

function report($code, $text) {
	global $errCode;

	$errCode = $code;
	writeln('  ! ' . $text);
}

function init() {
	mb_internal_encoding(ENCODING);
	mb_http_output(ENCODING);
	\ava12\tpl\Util::handleErrors();
}

function runTest($srcName, $source) {
	global $iniParser;

	$errorRe = '/^\\\\\\\\::\\s+((\\d+).*)$/m';
	$multiple = preg_match_all($errorRe, $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
	if ($multiple) {
		echo '(';

		$matches[] = [['', strlen($source)]];
		for ($i = 0; $i <= count($matches) - 2; $i++) {
			$name = $matches[$i][1][0];
			$name = "$srcName #" . ($i + 1) . " ($name)";
			$src = substr($source, $matches[$i][0][1], $matches[$i + 1][0][1] - $matches[$i][0][1]);
			$sources[] = [$src, $matches[$i][2][0], $name];
		}
	} else {
		$sources = [[$source, 0, $srcName]];
	}

	foreach ($sources as $entry) {
		$env = makeEnv($iniParser->getResult());
		\ava12\tpl\cli\TestLib::setup($env);
		$source = $entry[0];
		$expectedError = $entry[1];
		$gotError = 0;

		try {
			$env->parser->pushSource($source, $entry[2]);
			$env->parser->parse();
			$env->machine->run();

		} catch (\ava12\tpl\machine\RunException $e) {
			$gotError = $e->getCode();
			if ($expectedError <> $gotError) {
				throw $e;
			}

		} catch (\ava12\tpl\parser\ParseException $e) {
			$gotError = $e->getCode();
			if ($expectedError <> $gotError) {
				throw $e;
			}

		}

		if ($expectedError and !$gotError) {
			write(NL . $entry[2] . ': ожидается ошибка ' . $expectedError . ', код выполнен без ошибок' . NL);
			exit(3);
		}

		echo ($expectedError ? '!' : ':');
	}

	if ($multiple) echo ')';
}

function runGen($name, $source) {

}

function makeEnv($configs) {
	$config = new \ava12\tpl\env\Config($configs);
	$env = new \ava12\tpl\env\Env;
	$lib = new \ava12\tpl\lib\Loader;
	$env->init($config);
	$lib->init($env);

	return $env;
}