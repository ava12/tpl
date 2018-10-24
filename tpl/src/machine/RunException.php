<?php

namespace ava12\tpl\machine;

class RunException extends \ava12\tpl\AbstractException {
	const CUSTOM = 200;

	const STACK_FULL = 201;
	const CALL_DEPTH = 202;
	const ITEM_DEPTH = 203;
	const SET_CONST = 204;
	const ARI = 205;
	const REGEXP = 206;
	const INC = 207;
	const IMPURE = 208;

	const WRONG_OP = 301;
	const NO_CONTEXT = 302;
	const NO_VAR = 303;
	const VAR_TYPE = 304;
	const NO_FUNC = 305;
	const NO_LOOP = 306;
	const NO_TEMPLATE = 307;
	const STACK_TYPE = 308;
	const STACK_EMPTY = 309;
	const WRONG_CHUNK_TYPE = 310;
	const WRONG_TEMPLATE = 311;

	protected static $messages = [
		self::ARI => 'арифметическая ошибка (%s)',
		self::CALL_DEPTH => 'слишком большая глубина вызовов функций',
		self::CUSTOM => '%s',
		self::IMPURE => 'невозможно вызвать общую функцию из чистой',
		self::INC => 'невозможно включить файл "%s"',
		self::ITEM_DEPTH => 'слишком большая глубина преобразования типов',
		self::NO_CONTEXT => 'контекст с индексом %d недоступен',
		self::NO_FUNC => 'функция с индексом %d не определена',
		self::NO_LOOP => 'команда управления циклом вне цикла',
		self::NO_TEMPLATE => 'отсутствует шаблон для выбора',
		self::NO_VAR => 'отсутствует переменная с индексом %2$d в контексте %1$d',
		self::REGEXP => 'обработка регулярного выражения, код: %s',
		self::SET_CONST => 'невозможно изменить значение константы',
		self::STACK_EMPTY => 'исчерпан стек операндов',
		self::STACK_FULL => 'стек операндов переполнен',
		self::STACK_TYPE => 'некорректный тип элемента стека: %s',
		self::VAR_TYPE => 'некорректный тип переменной: %s',
		self::WRONG_CHUNK_TYPE => 'некорректный тип блока',
		self::WRONG_OP => 'некорректный код операции',
		self::WRONG_TEMPLATE => 'некорректный тип шаблона: %s',
	];


	/** @var DebugEntry $debugEntry */
	protected $debugEntry;

	public function getDebugEntry() {
		return $this->debugEntry;
	}

	/**
	 * @param int $code
	 * @param int|string|array $data
	 * @param DebugEntry $debugEntry
	 */
	public function __construct($code, $data = null, $debugEntry = null) {
		parent::__construct($code, $data);
		$this->debugEntry = $debugEntry;
	}

	public function setDebugEntry($debugEntry) {
		$this->debugEntry = $debugEntry;
	}

	public function formatDebugEntry() {
		$result = [];
		$entry = $this->debugEntry;
		while ($entry) {
			$result[] = "  {$entry->functionName}[{$entry->funcDef->index}:{$entry->chunkIndex}:{$entry->chunkIp}] ({$entry->sourceName} @ {$entry->line})" . PHP_EOL;
			$entry = $entry->callerEntry;
		}
		return implode('', $result);
	}

	public function getSourceId() { return null; }
	public function getSourceName() { return $this->debugEntry->sourceName; }
	public function getSourceLine() { return $this->debugEntry->line; }
	public function getSourceColumn() { return null; }
}
