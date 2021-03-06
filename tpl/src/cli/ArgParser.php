<?php

namespace ava12\tpl\cli;

class ArgParser {
	/** @var \ava12\tpl\cli\IniParser */
	protected $iniParser;
	/** @var \ava12\tpl\cli\Config */
	protected $cliConfig;

	const ERR_WRONG_ARGS = 2;
	const ERR_WRONG_INI = 3;

	const DEFAULT_INI_NAME = 'tpl.ini';
	const ENV_WORK_DIR = 'PWD';
	const ENV_INI_DIR = 'INI_DIR';

	const ARG_ARG = 'a';
	const ARG_CON_ENC = 'c';
	const ARG_ENV = 'e';
	const ARG_INI_NAME = 'i';
	const ARG_NO_INI = 'I';
	const ARG_EOL = 'l';
	const ARG_USE_MASK = 'm';
	const ARG_FS_ENC = 'n';
	const ARG_OUT_NAME = 'o';
	const ARG_OUT_CON = 'O';
	const ARG_SET = 's';
	const ARG_TEST = 't';
	const ARG_OUT_EXT = 'x';
	const ARG_FILES = '*';

	protected static $help = [
		'Запуск:  php tpl.php [-a <имя>=<значение>] [-c <кодировка>]',
		'  [-e <имя>=<значение>] [-i <имя>] [-n <кодировка>] [{-o <имя> | -O}]',
		'  [-s <имя>=<значение>] [-t] [-x <расширение>] [<файл> ...]',
		'',
		'Параметры:',
		'  -a <имя>=<значение>  добавить аргумент, то же, что и -s arg.<имя>=<значение>',
		'  -c <кодировка>       кодировка для вывода сообщений',
		'  -e <имя>=<значение>  добавить переменную окружения',
		'  -i <имя>             задать имя ini-файла, по умолчанию tpl.ini',
		'  -I                   не использовать ini-файл',
		'  -l <тип>             тип окончаний строк результата: CR, LF или CRLF',
		'  -m                   подставлять имена файлов в INI-параметр inputMask',
		'  -n <кодировка>       то же, что и -s file.nameEncoding=<кодировка>',
		'  -o <имя>             имя каталога для вывода',
		'  -O                   выводить результат на консоль',
		'  -s <имя>=<значение>  задать параметр настроек',
		'  -t                   режим тестирования',
		'  -x <расширение>      расширение (с точкой) файлов для вывода',
		'  <файл>               имя или шаблон имени входных файлов',
		'',
	];

	// {имя: [требует_значение?, множество?, имя=значение?]}
	protected static $argDef = [
		self::ARG_ARG => [true, true, true],
		self::ARG_CON_ENC => [true, false],
		self::ARG_ENV => [true, true, true],
		self::ARG_INI_NAME => [true, false],
		self::ARG_NO_INI => [false],
		self::ARG_EOL => [true, false],
		self::ARG_USE_MASK => [false],
		self::ARG_FS_ENC => [true, false],
		self::ARG_OUT_NAME => [true, false],
		self::ARG_OUT_CON => [false],
		self::ARG_SET => [true, true, true],
		self::ARG_TEST => [false],
		self::ARG_OUT_EXT => [true, false],
	];

	/**
	 * @param \ava12\tpl\cli\Config $cliConfig
	 * @param \ava12\tpl\cli\IniParser $iniParser
	 */
	public function __construct($cliConfig, $iniParser) {
		$this->cliConfig = $cliConfig;
		$this->iniParser = $iniParser;
	}

	public function parse(array $argv) {
		$args = $this->scan($argv);
		if (!$args) {
			throw new ArgException(implode(PHP_EOL, self::$help), self::ERR_WRONG_ARGS);
		}

		$this->configCli($args);
		$this->readIni($args);
	}

	protected function scan(array $argv) {
		array_shift($argv);
		$result = [self::ARG_FILES => []];
		foreach (self::$argDef as $flag => $def) {
			$result[$flag] = ($def[0] ? ($def[1] ? [] : null) : false);
		}

		while ($argv) {
			$arg = $argv[0];
			$flags = str_split($arg, 1);
			if ($flags[0] <> '-') {
				break;
			}

			array_shift($argv);
			array_shift($flags);
			foreach ($flags as $flag) {
				if (!isset(self::$argDef[$flag])) {
					return null;
				}

				$def = self::$argDef[$flag];
				if (!$def[0]) {
					$result[$flag] = true;
					continue;
				}

				$value = array_shift($argv);
				if (!isset($value)) {
					return null;
				}

				$value = $this->cliConfig->decodeCon($value);
				if (!$def[1]) {
					$result[$flag] = $value;
					continue;
				}

				$value = explode('=', $value, 2);
				if (count($value) < 2) return null;

				$k = $value[0];
				if (substr($k, -2) <> '[]') {
					$result[$flag][$k] = $value[1];
					continue;
				}

				$k = substr($k, 0, -2);
				if (!isset($result[$flag][$k])) {
					$result[$flag][$k] = [$value[1]];
				} else {
					$result[$flag][$k] = (array)$result[$flag][$k];
					$result[$flag][$k][] = $value[1];
				}
			}
		}

		foreach ($argv as $arg) {
			$result[self::ARG_FILES][] = $this->cliConfig->decodeCon($arg);
		}

		return $result;
	}

	protected function configCli(array $args) {
		$config = $this->cliConfig;

		if (isset($args[self::ARG_CON_ENC])) {
			$config->consoleEnc = $args[self::ARG_CON_ENC];
		}
		$config->testMode = $args[self::ARG_TEST];
		if ($args[self::ARG_FILES]) {
			$config->consoleMask = $args[self::ARG_FILES];
		}
		$config->outputDir = $args[self::ARG_OUT_NAME];
		$config->outputSuffix = $args[self::ARG_OUT_EXT];
		$config->stdout = $args[self::ARG_OUT_CON];
		$config->useMask = $args[self::ARG_USE_MASK];
		$config->eol = $args[self::ARG_EOL];
	}

	protected function readIni(array $args) {
		try {
			$iniParser = $this->iniParser;
			$iniName = '';

			if (empty($args[self::ARG_NO_INI])) {
				$iniName = (empty($args[self::ARG_INI_NAME]) ? self::DEFAULT_INI_NAME : $args[self::ARG_INI_NAME]);
				$iniParser->parseFile($iniName);
			}

			$varArgs = [
				self::ARG_FS_ENC => 'file.nameEncoding',
				self::ARG_SET => '',
				self::ARG_ARG => 'arg.',
			];
			foreach ($varArgs as $arg => $prefix) {
				foreach ((array)($args[$arg]) as $name => $value) {
					if (is_numeric($name)) {
						$name = '';
					}
					$iniParser->setValue($prefix . $name, $value);
				}
			}

			foreach ($args[self::ARG_ENV] as $name => $value) {
				$iniParser->setEnv($name, $value);
			}
			$iniParser->setEnv(self::ENV_WORK_DIR, getcwd());
			$iniParser->setEnv(self::ENV_INI_DIR, dirname(realpath($iniName)));

		} catch (\RuntimeException $e) {
			throw new ArgException($e->getMessage(), self::ERR_WRONG_INI);
		}
	}
}
