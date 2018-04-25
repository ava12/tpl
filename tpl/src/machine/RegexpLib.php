<?php

namespace ava12\tpl\machine;

use \ava12\tpl\Util;

class RegexpLib {
	protected static $methods = [
		'count' => ['callCount', 1],
		'first' => ['callFirst', 1],
		'last' => ['callLast', 1],
		'match' => ['callMatch', 1],
		'replace' => ['callReplace', 3],
		'split' => ['callSplit', 2],
	];

	/** @var Machine $machine */
	protected $machine;

	protected function __construct($machine) {
		$this->machine = $machine;
	}

	public static function setup($machine, $name = 'regexp') {
		$instance = new static($machine);
		$mainFunc = $machine->getFunction();
		FunctionProxy::inject($mainFunc, $name, [$instance, 'callRegexp'], 2, true);
	}

	protected function checkError() {
		$code = preg_last_error();
		if ($code) {
			throw new RunException(RunEexception::REGEXP, $code);
		}
	}

	public function callRegexp($args) {
		$pattern = addcslashes($this->machine->toString($args[0]), '/');
		$mods = $this->machine->toString($args[1]);
		if (!strstr($mods, 'u')) $mods .= 'u';
		$regexp = "/$pattern/$mods";

		try {
			preg_match($regexp, ' ');
		} catch (\Exception $e) {
			$message = explode(': ', $e->getMessage(), 2);
			throw new RunException(RunException::REGEXP, $message[1]);
		}
		$this->checkError();

		$result = new ListValue;
		$result->addItem(new Variable(new ScalarValue($regexp)), 'pattern');
		foreach (static::$methods as $name => $callback) {
			$proxy = new FunctionProxy([$this, $callback[0]], $callback[1], true, [], $regexp);
			$result->addItem(new Variable(new Closure($proxy), true), $name);
		}

		return new Variable($result, true);
	}


	public function callCount($args, $t, $c, $regexp) {
		$s = $this->machine->toString($args[0]);
		preg_match_all($regexp, $s, $m);
		$this->checkError();
		return count($m[0]);
	}

	protected function mapMatch($match, $pos) {
		if (!strlen($match)) return NullValue::getValue();

		$result = new ListValue();
		$result->addItem(new Variable(new ScalarValue($match)), 'match');
		$result->addItem(new Variable(new ScalarValue($pos + 1)), 'pos');
		return $result;
	}

	protected function mapResult($set, $string, &$prevByte, &$prevPos) {
		if (!$set) return new Variable;

		$prevPos += Util::strlen(substr($string, $prevByte, $set[0][1] - $prevByte));
		$prevByte = $set[0][1];
		$result = $this->mapMatch($set[0][0], $prevPos);
		$sub = new ListValue();

		for ($i = 1; $i < count($set); $i++) {
			if ($set[$i][1] == $prevByte) $pos = $prevPos;
			else $pos = $prevPos + Util::strlen(substr($string, $prevByte, $set[$i][1] - $prevByte));
			$sub->addItem(new Variable($this->mapMatch($set[$i][0], $pos)));
		}

		$result->addItem(new Variable($sub), 'sub');
		return new Variable($result);
	}

	public function callFirst($args, $t, $c, $regexp) {
		$s = $this->machine->toString($args[0]);
		preg_match($regexp, $s, $m, PREG_OFFSET_CAPTURE);
		$this->checkError();
		$b = 0;
		$p = 0;
		return $this->mapResult($m, $s, $b, $p);
	}

	public function callLast($args, $t, $c, $regexp) {
		$s = $this->machine->toString($args[0]);
		preg_match_all($regexp, $s, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		$this->checkError();
		if (!$m) return null;

		$b = 0;
		$p = 0;
		return $this->mapResult(array_pop($m), $s, $b, $p);
	}

	public function callMatch($args, $t, $c, $regexp) {
		$s = $this->machine->toString($args[0]);
		preg_match_all($regexp, $s, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		$this->checkError();

		$result = new ListValue;
		$b = 0;
		$p = 0;
		foreach ((array)$m as $set) $result->addItem($this->mapResult($set, $s, $b, $p));
		return new Variable($result);
	}

	public function callReplace($args, $t, $c, $regexp) {
		$machine = $this->machine;
		$s = $machine->toString($args[0]);
		$r = $args[1];
		$limit = $machine->toScalar($args[2]);
		$limit = ($limit->isNull() ? -1 : $limit->getValue()->asInt());

		$c = null;
		while ($r->isContainer()) {
			$c = $r;
			$r = $r->getByIndex(1);
		}

		if (!$r->isCallable()) {
			$r = $r->getValue()->asString();
			$result = preg_replace($regexp, $r, $s, $limit);
			$this->checkError();
			return $result;
		}

		$callback = function ($matches) use ($machine, $r, $c) {
			$args = new ListValue;
			$sub = new ListValue;

			$args->addItem(new Variable(new ScalarValue($matches[0])), 'match');
			for ($i = 1; $i < count($matches); $i++) {
				$sub->addItem(new Variable(new ScalarValue($match)));
			}
			$args->addItem(new Variable($sub), 'sub');
			$replace = $machine->callVar($r, $args, $c);
			return $machine->toString($replace);
		};

		$result = preg_replace_callback($regexp, $callback, $s, $limit);
		$this->checkError();
		return $result;
	}

	public function callSplit($args, $t, $c, $regexp) {
		$s = $this->machine->toString($args[0]);
		$limit = $this->machine->toScalar($args[1]);
		$limit = ($limit->isNull() ? -1 : $limit->getValue()->asInt());
		$matches = preg_split($regexp, $s, $limit);
		$result = new ListValue;
		foreach ($matches as $match) {
			$result->addItem(new Variable(new ScalarValue($match)));
		}
		return new Variable($result);
	}
}
