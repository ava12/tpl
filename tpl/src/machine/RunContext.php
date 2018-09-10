<?php

namespace ava12\tpl\machine;

class RunContext {
	const MAX_STACK_DEPTH = 1023;
	const MAX_CALL_DEPTH = 31;
	const MAX_ITEM_DEPTH = 31;

	protected static $blockTypes = [CodeChunk::TYPE_DO, CodeChunk::TYPE_LOOP];

	/** @var RunContext $parentContext */
	public $parentContext;
	public $functionName;
	/** @var Context */
	public $context;
	/** @var StackItem[] */
	public $stack = [];
	/** @var CodeChunk */
	public $codeChunk;
	public $ip = 0;
	protected $chunkStack = []; // [[chunk, ip]*]
	/** @var IIterator[] */
	protected $iteratorStack = [];
	protected $templateStack = []; // [[value, flag]]
	public $callDepth = 0;
	protected $itemDepth = 0;
	/** @var FunctionDef */
	protected $functionDef;
	protected $hasOutput = true;

	/**
	 * @param string $functionName
	 * @param Context|null $context
	 * @param RunContext|null $parentContext
	 * @param Variable|false|null $output
	 */
	public function __construct($functionName, $context = null, $parentContext = null, $output = null) {
		$this->functionName = $functionName;
		$this->context = $context;
		if ($context) {
			$this->functionDef = $context->getFunctionDef();
			$this->codeChunk = $this->functionDef->getCodeChunk();
			$this->parentContext = $parentContext;
		}

		if ($parentContext) {
			$this->callDepth = $parentContext->callDepth + 1;
			if ($this->callDepth >= static::MAX_CALL_DEPTH) {
				throw new RunException(RunException::CALL_DEPTH);
			}
		}

		$this->hasOutput = !(isset($output) and !$output);
		if (!$output) $output = new Variable;
		$this->stack = [new StackItem($output)];
	}

	public function fetch() {
		while (true) {
			if (!$this->codeChunk) return null;

			if ($this->ip >= count($this->codeChunk->code)) {
				if ($this->codeChunk->type == CodeChunk::TYPE_LOOP) {
					$this->ip = 0;
				} else {
					$this->dropCodeChunk();
					continue;
				}
			}

			if (!$this->ip and $this->codeChunk->type == CodeChunk::TYPE_LOOP) {
				if (!$this->iteratorStack[0]->next()) {
					$this->dropCodeChunk();
					continue;
				}
			}

			break;
		}

		$result = $this->codeChunk->code[$this->ip];
		$this->ip++;
		return $result;
	}

	public function stepBack() {
		$this->ip--;
	}

	public function getDebugEntry($sources) {
		$caller = ($this->parentContext ? $this->parentContext->getDebugEntry($sources) : null);
		$info = $this->codeChunk->findDebugEntry($this->ip - 1);
		$sourceName = $sources[$info['sourceId']];
		return new DebugEntry($this->functionName, $sourceName, $info['line'], $caller, $this->functionDef, $this->codeChunk->index, $this->ip - 1);
	}

	public function pushCodeChunk($index) {
		$this->chunkStack[] = [$this->codeChunk, $this->ip];
		$this->codeChunk = $this->functionDef->getCodeChunk($index);
		$this->ip = 0;
	}

	public function pushLoopChunk($index, $iterator) {
		$this->pushCodeChunk($index);
		if ($this->codeChunk->type <> CodeChunk::TYPE_LOOP) {
			throw new RunException(RunException::WRONG_CHUNK_TYPE);
		}

		array_unshift($this->iteratorStack, $iterator);
	}

	public function pushCaseChunk($index, $value) {
		$this->pushCodeChunk($index);
		if ($this->codeChunk->type <> CodeChunk::TYPE_CASE) {
			throw new RunException(RunException::WRONG_CHUNK_TYPE);
		}

		array_unshift($this->templateStack, [$value, false]);
	}

	public function dropCodeChunk() {
		switch ($this->codeChunk->type) {
			case CodeChunk::TYPE_LOOP:
				array_shift($this->iteratorStack);
			break;

			case CodeChunk::TYPE_CASE:
				array_shift($this->templateStack);
			break;
		}

		$entry = array_pop($this->chunkStack);
		if (!$entry) {
			$this->codeChunk = null;
			return null;
		}

		$this->codeChunk = $entry[0];
		$this->ip = $entry[1];
	}

	public function push($item) {
		if (count($this->stack) >= static::MAX_STACK_DEPTH) {
			throw new RunException(RunException::STACK_FULL);
		}

		$this->stack[] = $item;
	}

	/**
	 * @param int $depth
	 * @return StackItem
	 */
	public function pop($depth = 0) {
		$cnt = count($this->stack) - 1;
		if ($cnt <= $depth) {
			throw new RunException(RunException::STACK_EMPTY);
		}

		if ($depth) {
			return array_splice($this->stack, $cnt - $depth, 1)[0];
		} else {
			return array_pop($this->stack);
		}
	}

	/**
	 * @param int $count
	 * @return StackItem[]
	 */
	public function popMulti($count) {
		if (count($this->stack) <= $count) {
			throw new RunException(RunException::STACK_EMPTY);
		}

		return array_splice($this->stack, -$count, $count);
	}

	/**
	 * @param int $depth
	 * @return StackItem
	 */
	public function peek($depth = 0) {
		$cnt = count($this->stack);
		if ($cnt < $depth) {
			throw new RunException(RunException::STACK_EMPTY);
		}

		return $this->stack[$cnt - $depth - 1];
	}

	public function poke($item, $depth = 0) {
		$cnt = count($this->stack);
		if ($cnt < $depth) {
			throw new RunException(RunException::STACK_EMPTY);
		}

		$this->stack[$cnt - $depth - 1] = $item;
	}

	public function goDeeper() {
		$this->itemDepth++;
		if ($this->itemDepth >= static::MAX_ITEM_DEPTH) {
			throw new RunException(RunException::ITEM_DEPTH);
		}
	}

	public function surface() {
		$this->itemDepth = 0;
	}

	public function getOutput() {
		return ($this->hasOutput ? $this->stack[0]->value : false);
	}

	public function setOutput($var) {
		if ($this->hasOutput) $this->stack[0] = new StackItem($var);
	}

	public function continueLoop() {
		while (!in_array($this->codeChunk->type, static::$blockTypes)) {
			$this->dropCodeChunk();
			if (!$this->codeChunk) {
				throw new RunException(RunException::NO_LOOP);
			}
		}

		switch ($this->codeChunk->type) {
			case CodeChunk::TYPE_LOOP:
				$this->ip = 0;
			break;

			case CodeChunk::TYPE_DO:
				$this->dropCodeChunk();
			break;
		}
	}

	public function breakLoop() {
		while (!in_array($this->codeChunk->type, static::$blockTypes)) {
			$this->dropCodeChunk();
			if (!$this->codeChunk) {
				throw new RunException(RunException::NO_LOOP);
			}
		}

		$this->dropCodeChunk();
	}

	public function getTemplateValue() {
		if ($this->templateStack) return $this->templateStack[0][0];

		throw new RunException(RunException::NO_TEMPLATE);
	}

	public function getTemplateFlag() {
		if ($this->templateStack) return $this->templateStack[0][1];

		throw new RunException(RunException::NO_TEMPLATE);
	}

	public function setTemplateFlag() {
		if ($this->templateStack) {
			$this->templateStack[0][1] = true;
			return;
		}

		throw new RunException(RunException::NO_TEMPLATE);
	}

	public function finish() {
		$this->codeChunk = null;
		$this->chunkStack = [];
		$this->iteratorStack = [];
		$this->templateStack = [];
	}

	public function isPure() {
	    return $this->functionDef->isPure();
    }
}
