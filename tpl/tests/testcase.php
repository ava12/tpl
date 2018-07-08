<?php

/*
	Запуск: php testcase.php [-e<выходная_кодировка>] [-f<кодировка_файловой_системы>] <имя_файла>
	Пример: php testcase.php -eCP866 -fCP1251 errors.tpl

	Останавливается на первом же проваленном тесте. Печатает:
		( - начало файла с несколькими тестами
		) - конец файла с несколькими тестами
		: - успешное выполнение теста, в котором не ожидается ошибок
		! - успешное выполнение теста, в котором ожидается ошибка с указанным кодом
		. - успешный вызов функции assert()

	Файл может содержать несколько тестов (отдельных программ). В этом случае
	каждая программа предваряется однострочным комментарием вида
		\\:: код_ошибки [описание теста]
	в самом начале строки, где код_ошибки = 0, если ошибка не ожидается.
	Если такого комментария нет, то файл содержит только один тест, в котором
	не ожидается ошибок.
*/

mb_internal_encoding('utf8');
define('NL', PHP_EOL);
define('CDL', 20);

require_once '../autoload.php';
require_once 'TestFunction.php';

use \ava12\tpl\machine\Machine;
use \ava12\tpl\parser\Parser;
use \ava12\tpl\parser\Token;
use \ava12\tpl\parser\MacroProcessor;
use \ava12\tpl\machine\StdLib;
use \ava12\tpl\machine\RegexpLib;
use \ava12\tpl\machine\FileSys;
use \ava12\tpl\machine\FileLib;
use \ava12\tpl\Util;
use \ava12\tpl\env\Env;
use \ava12\tpl\env\Config;
use \ava12\tpl\lib\Loader;

$fileName = null;
$encoding = null;
$fileEncoding = null;


function write($text) {
	global $encoding;
	if ($encoding) $text = mb_convert_encoding($text, $encoding);
	echo $text;
}

array_shift($argv);
foreach ($argv as $arg) {
	if (substr($arg, 0, 1) <> '-') {
		$fileName = $arg;
		break;
	}

	switch (substr($arg, 1, 1)) {
		case 'e':
			$encoding = substr($arg, 2);
		break;

		case 'f':
			$fileEncoding = substr($arg, 2);
		break;
	}
}

if (!$fileName) {
	write(NL . 'Запуск:  php testcase.php [-e<кодировка>] [-f<кодировка_фс>] <имя_файла>' . NL);
	exit(1);
}

$source = @file_get_contents($fileName);
if ($source === false) {
	write(NL . 'Отсутствует файл теста' . NL);
	exit(2);
}

Util::handleErrors();

$sources = [];
$source = preg_replace('/\\r\\n?/', "\n", $source);
$errorRe = '/^\\\\\\\\::\\s+((\\d+).*)$/m';
$multiple = preg_match_all($errorRe, $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
if ($multiple) {
	echo '(';

	$matches[] = [['', strlen($source)]];
	for ($i = 0; $i <= count($matches) - 2; $i++) {
		$name = $matches[$i][1][0];
		$name = "$fileName #" . ($i + 1) . " ($name)";
		$src = substr($source, $matches[$i][0][1], $matches[$i + 1][0][1] - $matches[$i][0][1]);
		$sources[] = [$src, $matches[$i][2][0], $name];
	}
} else {
	$sources = [[$source, 0, $fileName]];
}

$config = new Config;

if ($fileEncoding) $config->file->nameEncoding = $fileEncoding;
$config->file->nameChars = 'А-Яа-яЁё';
$rootDir = __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
$fsData = require(__DIR__ . DIRECTORY_SEPARATOR . 'files.php');
foreach ($fsData as $name => $data) {
	$config->file->addRoot($name, $rootDir . $name, $data[0]);
}

$config->lib->addLib('test', 'TestFunction');
$config->lib->apply(['lib' => ['meta', 'std', 'regexp', 'file']]);

foreach ($sources as $entry) {
	$env = new Env;
	$lib = new Loader;
	$env->init($config);
	$lib->init($env);

	$machine = $env->machine;
	$parser = $env->parser;

	$source = $entry[0];
	$expectedError = $entry[1];
	$gotError = 0;

	try {
		$parser->pushSource($source, $entry[2]);
//		$parser->setStringHandler(Token::TYPE_STRING_PERCENT, new MacroProcessor($machine));
		$parser->parse();
		$machine->run();

	} catch (\ava12\tpl\machine\RunException $e) {
		$gotError = $e->getCode();
		if ($expectedError <> $gotError) {
			write(NL . $e->getMessage() . NL . $e->formatDebugEntry());

			$de = $e->getDebugEntry();
			$errorIp = $de->chunkIp;
			$ip = max(0, $errorIp - CDL + 3);
			if (isset($de->funcDef)) {
				$code = array_slice($de->funcDef->getCodeChunk($de->chunkIndex)->code, $ip, CDL, true);
				foreach ($code as $ip => &$p) {
					if (is_array($p)) $p = '[' . implode(',', $p) . ']';
					if ($ip == $errorIp) $p = '>' . $p . '<';
				}
				write('код: ' . implode(', ', $code) . NL);
			}

			exit(3);
		}

	} catch (\ava12\tpl\parser\ParseException $e) {
		$gotError = $e->getCode();
		if ($expectedError <> $gotError) {
			write(NL . $e->getMessage() . NL);
			exit(3);
		}

	} catch (Exception $e) {
		write(NL . $fileName . ': ' . NL . $e . NL);
		exit(3);
	}

	if ($expectedError and !$gotError) {
		write(NL . $entry[2] . ': ожидается ошибка ' . $expectedError . ', код выполнен без ошибок' . NL);
		exit(3);
	}

	echo ($expectedError ? '!' : ':');
}

if ($multiple) echo ')';
