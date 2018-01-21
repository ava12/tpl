<?php

namespace ava12\tpl\machine;

class ForIterator implements IIterator {
	/** @var StackItem */
	protected $target;
	protected $value;
	protected $totalIterations;
	protected $iterationsLeft;
	protected $step;

	/**
	 * @param StackItem $target
	 * @param int $beginValue
	 * @param int $endValue
	 * @param int $step
	 */
	public function __construct($target, $beginValue, $endValue, $step) {
//		if (!$step) {
//			throw new RunException(RunException::ITERATIONS_CNT);
//		}

		$valueDiff = $endValue - $beginValue;
		if ((($valueDiff > 0) xor ($step > 0)) and $valueDiff) {
			$this->totalIterations = 0;
		} else {
			$this->totalIterations = (int)($valueDiff / $step) + 1;
		}

		$this->iterationsLeft = $this->totalIterations;
		$this->target = $target;
		$this->value = $beginValue;
		$this->step = $step;
	}

	public function count() {
		return $this->totalIterations;
	}

	public function next() {
		$this->target->setVar(new Variable(new ScalarValue($this->value)));
		if ($this->iterationsLeft) {
			$this->iterationsLeft--;
			$this->value += $this->step;
			return true;
		} else {
			return false;
		}
	}
}
