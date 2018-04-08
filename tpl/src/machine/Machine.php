<?php

namespace ava12\tpl\machine;

use \ava12\tpl\Util;

class Machine {
	const MAX_NEST_LEVEL = 31;

	/** @var FunctionDef[] */
	protected $functions = [];
	/** @var Context */
	protected $rootContext;
	/** @var RunContext */
	protected $runContext;
	protected $sources = [];
	protected $args;


	public function __construct() {
		$this->functions = [new MainFunctionDef];
		$this->functions[0]->addCodeChunk();
	}

	public function setArgs($args) {
		$this->args = $args;
	}

	public function getFunction($index = 0) {
		if (isset($this->functions[$index])) return $this->functions[$index];
		else return null;
	}

	protected function makeRootContext() {
		if ($this->rootContext) return;

		$main = $this->functions[0];
		$this->rootContext = new Context(null, $main, null, $this->args);
	}

	public function getRootContext() {
		if (!$this->rootContext) $this->makeRootContext();
		return $this->rootContext;
	}

	protected function init() {
		$this->makeRootContext();
		$this->runContext = new RunContext('-main-', $this->rootContext);
	}

	/**
	 * @param FunctionDef|null $parent
	 * @param bool $isPure
	 * @return FunctionDef
	 */
	public function addFunction($parent = null, $isPure = false) {
		$index = count($this->functions);
		$parentIndex = ($parent ? (int)$parent->index : null);
		if (!isset($parentIndex)) $result = new MainFunctionDef();
		else $result = new FunctionDef($index, $parentIndex, $isPure);
		$this->functions[$index] = $result;
		return $result;
	}

	public function addSourceName($name, $unique = false) {
		if ($unique and in_array($name, $this->sources)) return false;

		$sourceId = count($this->sources) + 1;
		$this->sources[$sourceId] = $name;
		return $sourceId;
	}

	protected function runOp($op) {
		if (is_string($op)) {
			if (!strlen($op)) $op = null;
			elseif (substr($op, 0, 1) == '$') $op = (string)substr($op, 1);
			else $op = [$op];
		}
		if (is_array($op)) {
			$opName = $op[0];
			$handler = 'op' . ucfirst($opName);
			try {
				$runContext = $this->runContext;
				if (!$this->$handler($op)) {
					$runContext->stepBack();
				}
			} catch (RunException $e) {
				if (!$e->getDebugEntry()) {
					$debugEntry = $this->runContext->getDebugEntry($this->sources);
					$e->setDebugEntry($debugEntry);
				}
				throw $e;
			}

		} else {
			$value = (isset($op) ? new ScalarValue($op) : NullValue::getValue());
			$this->runContext->push(new StackItem(new Variable($value)));
		}
	}

	protected function runRet() {
		$result = $this->runContext->getOutput();
		$this->runContext = $this->runContext->parentContext;

		if (!$this->runContext) return $result;

		if ($result) $this->runContext->push(new StackItem($result));
		return null;
	}

	public function run($steps = null) {
		if (!$this->runContext) $this->init();

		while (true) {
			$op = $this->runContext->fetch();
			if (isset($op)) {
				$this->runOp($op);
			} else {
				$result = $this->runRet();
				if (isset($result)) return $result;
			}

			if (isset($steps)) {
				$steps--;
				if ($steps <= 0) return null;
			}

		}

		return null;
	}

	protected function makeException($code, $data = null) {
		$debugEntry = $this->runContext->getDebugEntry($this->sources);
		return new RunException($code, $data, $debugEntry);
	}

	protected function callDeeper() {
		$this->runContext->goDeeper();
		$item = $this->runContext->pop();
		$var = $item->asVar();
		$closure = $var->getValue();
		/** @var Closure $closure */
		if ($closure->func->getType() == IValue::TYPE_FUNCTION) {
			$name = $item->path;
			$thisVar = ($item->container ? new Variable($item->container) : null);
			$context = new Context($closure->context, $closure->func, $thisVar);
			$this->runContext = new RunContext($name, $context, $this->runContext);
		} else {
			$result = $closure->func->call($closure->context, $item->container, null);
			$this->runContext->push(new StackItem($result));
		}
	}

	public function computeExpression($func, $name = '') {
		$this->makeRootContext();
		$runContext = $this->runContext;
		$context = new Context($this->rootContext, $func);
		$this->runContext = new RunContext($name, $context);
		$result = $this->run();
		$this->runContext = $runContext;
		return $result;
	}

	/**
	 * @param Variable $var
	 * @param IListValue|null $args
	 * @param Variable|null $thisVar
	 * @return Variable
	 */
	public function callVar($var, $args = null, $thisVar = null) {
		while ($var->isContainer()) {
			$var = $var->getValue()->getByIndex(1);
		}

		if ($var->isFuncDef()) {
			$var = new Variable(new Closure($var->getValue(), $this->runContext->context));
		}

		if (!$var->isCallable()) return $var;

		$closure = $var->getValue();
		if ($closure->func->getType() == IValue::TYPE_FUNCTION) {
			$name = '[closure]';
			$runContext = $this->runContext;
			$context = new Context($closure->context, $closure->func, $thisVar, $args);
			$this->runContext = new RunContext($name, $context);
			$result = $this->run();
			$this->runContext = $runContext;
		} else {
			$thisList = ($thisVar ? $thisVar->getValue() : null);
			$result = $closure->func->call($closure->context, $thisList, $args);
		}

		return $result;
	}

	protected function nestDeeper(&$nestLevel) {
		$nestLevel++;
		if ($nestLevel > static::MAX_NEST_LEVEL) {
			throw $this->makeException(RunException::ITEM_DEPTH);
		}
	}

	/**
	 * @param Variable $var
	 * @param int $nestLevel
	 * @return Variable
	 */
	public function toData($var, $nestLevel = 0) {
		while ($var->isCallable()) {
			$this->nestDeeper($nestLevel);
			$closure = $var->getValue();
			/** @var Closure $closure */
			if ($closure->func->getType() == IValue::TYPE_FUNCTION_OBJ) {
				$var = $closure->func->call($closure->context);
				if (!isset($var)) $var = new Variable;
			} else {
				$runContext = $this->runContext;
				$context = new Context($closure->context, $closure->func);
				$this->runContext = new RunContext('', $context);
				$var = $this->run();
				$this->runContext = $runContext;
			}
		}

		return $var;
	}

	/**
	 * @param Variable $var
	 * @return Variable
	 */
	public function toList($var) {
		if ($var->isCallable()) $var = $this->toData($var);
		if (!$var->isContainer()) $var = new Variable(new ListValue($var));
		return $var;
	}

	/**
	 * @param Variable $var
	 * @param int $nestLevel
	 * @return Variable
	 */
	public function toScalar($var, $nestLevel = 0) {
		while (!$var->isScalar()) {
			$this->nestDeeper($nestLevel);
			if ($var->isCallable()) {
				$var = $this->toData($var, $nestLevel);
			} else {
				$var = $var->getValue()->getByIndex(1);
				if (!$var) $var = new Variable;
			}
		}
		return $var;
	}


	public function toString(Variable $var) {
		return $this->toScalar($var)->getValue()->asString();
	}

	public function toNumber(Variable $var) {
		return $this->toScalar($var)->getValue()->asNumber();
	}

	public function toInt(Variable $var) {
		return $this->toScalar($var)->getValue()->asInt();
	}

	public function toBool(Variable $var) {
		return $this->toScalar($var)->getValue()->asBool();
	}

	protected function opToval() {
		$var = $this->runContext->peek()->asVar();
		if ($var->isCallable()) {
			$this->callDeeper();
			return false;
		} else {
			$this->runContext->surface();
			return true;
		}
	}

	protected function opToscalar() {
		$item = $this->runContext->peek();
		$var = $item->asVar();
		if ($var->isScalar()) return true;

		$container = $item->container;
		$path = $item->path;

		while (!$var->isScalar()) {
			if ($var->isCallable()) {
				$this->runContext->poke(new StackItem($var, StackItem::TYPE_VALUE, $container, $path));
				$this->callDeeper();
				return false;
			}

			while ($var and $var->isContainer()) {
				$this->runContext->goDeeper();
				$container = $var->getValue();
				$path = 1;
				$var = $container->getByIndex(1);
			}

			if (!$var) {
				$var = new Variable;
				break;
			}
		}

		$this->runContext->poke(new StackItem($var));
		$this->runContext->surface();
		return true;
	}

	protected function toScalarValue($funcName, $default) {
		if (!$this->opToscalar()) return false;

		$value = $this->runContext->peek()->asVar()->getValue();
		$call = [$value, $funcName];
		if ($value->getType() == IValue::TYPE_NULL) {
			$value = $default;
		} else {
			$value = call_user_func($call);
		}
		$this->runContext->poke(new StackItem(new Variable(new ScalarValue($value))));
		return true;
	}

	protected function opTonum() {
		return $this->toScalarValue('asNumber', 0);
	}

	protected function opToint() {
		return $this->toScalarValue('asInt', 0);
	}

	protected function opTostr() {
		return $this->toScalarValue('asString', '');
	}

	protected function opTobool() {
		return $this->toScalarValue('asBool', false);
	}

	protected function opFunc($op) {
		$funcIndex = $op[1];
		$funcDef = $this->getFunction($funcIndex);
		if (!$funcDef) throw new RunException(RunException::NO_FUNC, $funcIndex);

		$context = $this->runContext->context;
		$var = new Variable(new Closure($funcDef, $context));
		$item = new StackItem($var);
		$this->runContext->push($item);
		return true;
	}

	protected function opVar($op) {
		$varIndex = $op[1];
		$functionIndex = (isset ($op[2]) ? $op[2] : null);

		$context = $this->runContext->context;
		if (isset($functionIndex)) {
			$context = $context->findContext($functionIndex);
			if (!$context) {
				throw new RunException(RunException::NO_CONTEXT, $functionIndex);
			}
		}

		$var = $context->getByIndex($varIndex);
		if (!$var) {
			throw new RunException(RunException::NO_VAR, [$functionIndex, $varIndex]);
		}

		if ($var->isFuncDef()) {
			$value = $var->getValue();
			$newVar = new Variable(new Closure($value, $context), $var->deref()->isConst());
			$var = ($var->isRef() ? new Reference($newVar, $var->isConst()) : $newVar);
		}

		$name = $context->getVarName($varIndex);
		$item = new StackItem($var, StackItem::TYPE_VALUE, $context, $name);
		$this->runContext->push($item);
		return true;
	}

	protected function opItem() {
		if (!$this->opToscalar()) return false;

		$keyVar = $this->runContext->peek()->asVar();
		$stackContainer = $this->runContext->peek(1);
		$container = $stackContainer->asVar();
		$value = $container->getValue();
		if ($container->isCallable()) {
			throw new RunException(RunException::VAR_TYPE, $value->getType());
		}

		$this->runContext->pop();
		$key = $keyVar->getValue();
		if ($keyVar->isNull()) {
			if ($container->isContainer()) $key = $container->getValue()->getCount() + 1;
			else $key = ($container->isNull() ? 1 : 2);
		} elseif (!$key->isNumber()) {
			$key = $key->asString();
		}
		else {
			$key = $key->asInt();
		}

		if ($stackContainer->type == StackItem::TYPE_NULL) {
			$stackContainer->path[] = $key;
			return true;
		}

		if ($container->isScalar()) {
			if ($key !== 0 and $key !== 1) {
				$container = $stackContainer->container;
				$path = [$stackContainer->path, $key];
				$item = new StackItem(new Variable, StackItem::TYPE_NULL, $container, $path);
				$this->runContext->poke($item);
			}
			return true;
		}

		if (is_string($key)) {
			/** @var IListValue $value */
			$var = $value->getByKey($key);
		} else {
			/** @var IListValue $value */
			$var = $value->getByIndex($key);
		}

		if (isset($var)) {
			$item = new StackItem($var, StackItem::TYPE_VALUE, $value, $key);
		} else {
			$item = new StackItem(new Variable, StackItem::TYPE_NULL, $value, [$key]);
		}

		$this->runContext->poke($item);
		return true;
	}

	protected function opPair() {
		$item = $this->runContext->pop();
		$key = $this->runContext->pop()->asVar();
		if (!$key->isScalar()) {
			throw new RunException(RunException::VAR_TYPE, $key->getValue()->getType());
		}

		$key = $key->getValue()->asString();
		$this->runContext->push(new StackItem($item->value, StackItem::TYPE_PAIR, null, $key));
		return true;
	}

	protected function opMklist($op) {
		$cnt = $op[1];
		$list = new ListValue;
		if ($cnt > 0) {
			foreach ($this->runContext->popMulti($cnt) as $item) {
				$list->addItem($item->value, $item->getKey());
			}
		}

		$this->runContext->push(new StackItem(new Variable($list)));
		return true;
	}

	protected function opMkref() {
		$item = $this->runContext->peek();
		$this->runContext->poke(new StackItem($item->value->ref()));
		return true;
	}

	protected function opDeref() {
		$item = $this->runContext->peek();
		$this->runContext->poke(new StackItem($item->value->deref()));
		return true;
	}

	protected function opSet() {
		$value = $this->runContext->pop()->value;
		$target = $this->runContext->pop();
		$target->setVar($value->copy());
		return true;
	}

	protected function opCat() {
		$runContext = $this->runContext;
		$var = $runContext->pop()->value;
		if ($var->isNull()) return true;

		$target = $runContext->peek();
		$targetVar = $target->value;
		if ($targetVar->isConst() and !$targetVar->isCallable()) {
			throw new RunException(RunException::SET_CONST);
		}

		switch ($targetVar->getType()) {
			case IValue::TYPE_NULL:
				$target->setVar($var->copy());
			break;

			case IValue::TYPE_SCALAR:
				if (!$var->isScalar()) {
					$runContext->push(new StackItem($var));
					if (!$this->opToscalar()) return false;

					$var = $runContext->pop()->asVar();
				}
				$targetVar->getValue()->concat($var->getValue());
			break;

			case IValue::TYPE_LIST:
				$targetVar->getValue()->concat($var->copy());
			break;

			case IValue::TYPE_CLOSURE:
				$args = new ListValue($var);
				$closure = $targetVar->getValue();
				$container = ((isset($target->container) and $target->container->isPublic()) ? $target->container : null);
				/** @var Closure $closure */
				if ($closure->func->getType() == IValue::TYPE_FUNCTION_OBJ) {
					$closure->func->call($closure->context, $container, $args);
				} else {
					$name = $target->path;
					$thisVar = ($container ? new Variable($container) : null);
					$context = new Context($closure->context, $closure->func, $thisVar, $args);
					$this->runContext = new RunContext($name, $context, $this->runContext, false);
				}
			break;

			default:
				throw new RunException(RunException::VAR_TYPE, $var->getType());
		}

		return true;
	}

	protected function opCall($op) {
		$cnt = $op[1];
		$this->opMklist(['mklist', $cnt]);
		$args = $this->runContext->pop()->asVar()->getValue();

		$item = $this->runContext->peek();
		$func = $item->asVar()->getValue();
		if ($func->getType() == IValue::TYPE_CLOSURE) {
			/** @var Closure $func */
			if ($func->func->getType() == IValue::TYPE_FUNCTION_OBJ) {
				$result = $func->func->call($func->context, $item->container, $args);
				$this->runContext->poke(new StackItem($result));
			} else {
				$this->runContext->pop();
				$thisList = ((isset($item->container) and $item->container->isPublic()) ? $item->container : null);
				$context = new Context($func->context, $func->func, new Variable($thisList), $args);
				$runContext = new RunContext($item->path, $context, $this->runContext);
				$this->runContext = $runContext;
			}
		}

		return true;
	}

	protected function opDup($op) {
		array_shift($op);
		if (!$op) $op = [0];
		$items = [];
		$runContext = $this->runContext;
		foreach ($op as $depth) {
			$items[] = $runContext->peek($depth);
		}
		foreach ($items as $item) $runContext->push($item);
		return true;
	}

	protected function opDrop($op) {
		array_shift($op);
		if (!$op) $op = [0];
		$offset = 0;
		$runContext = $this->runContext;
		foreach ($op as $depth) {
			$runContext->pop($depth - $offset);
			$offset++;
		}
		return true;
	}

	protected function opSwap($op) {
		$depth = (isset($op[1]) ? $op[1] : 1);
		$runContext = $this->runContext;
		$top = $runContext->pop();
		$sub = $runContext->peek($depth);
		$runContext->poke($top, $depth);
		$runContext->push($sub);
	}

	protected function opIf($op) {
		if (!$this->opTobool()) return false;

		$flag = $this->runContext->pop()->asVar()->asBool();
		if (!$flag and !isset($op[2])) return true;

		$chunkIndex = ($flag ? $op[1] : $op[2]);
		$this->runContext->pushCodeChunk($chunkIndex);
		return true;
	}

	protected function opNif($op) {
		if (!$this->opTobool()) return false;

		$flag = $this->runContext->pop()->asVar()->asBool();
		if (!$flag) {
			$this->runContext->pushCodeChunk($op[1]);
		}
		return true;
	}

	protected function opCase($op) {
		if (!$this->opToscalar()) return false;

		$runContext = $this->runContext;
		$value = $runContext->pop()->value->getValue()->getRawValue();
		$runContext->pushCaseChunk($op[1], $value);
		return true;
	}

	protected function opWhen($op) {
		if (!$this->opToscalar()) return false;

		$runContext = $this->runContext;
		$got = $runContext->getTemplateValue();
		$expected = $runContext->pop()->asVar()->getValue();
		$matched = false;
		switch ($expected->getType()) {
			case IValue::TYPE_NULL:
			case IValue::TYPE_SCALAR:
				$matched = Util::compareScalars($expected->getRawValue(), $got);
			break;

			case IValue::TYPE_LIST:
				for ($i = 1; $i < $expected->getCount(); $i++) {
					$item = $expected->getByIndex($i);
					if (!$item->isScalar()) {
						$type = $item->getValue()->getType();
						throw new RunException(RunException::WRONG_TEMPLATE, $type);
					}

					$matched = Util::compareScalars($item->getValue()->getRawValue(), $got);
					if ($matched) break;
				}

			break;

			default:
				throw new RunException(RunException::WRONG_TEMPLATE, $expected->getType());
		}

		if ($matched) {
			$runContext->setTemplateFlag();
			$runContext->pushCodeChunk($op[1]);
		}

		return true;
	}

	protected function opDo($op) {
		$this->runContext->pushCodeChunk($op[1]);
		return true;
	}

	protected function opFor($op) {
		$runContext = $this->runContext;
		$step = $runContext->pop()->asVar()->asScalar()->asInt();
		$end = $runContext->pop()->asVar()->asScalar()->asInt();
		$begin = $runContext->pop()->asVar()->asScalar()->asInt();
		$target = $runContext->pop();
		if ($step) {
			$iterator = new ForIterator($target, $begin, $end, $step);
			$runContext->pushLoopChunk($op[1], $iterator);
		}
		return true;
	}

	protected function opEach($op) {
		$runContext = $this->runContext;
		$indexTarget = $runContext->pop();
		$keyTarget = $runContext->pop();
		$valueTarget = $runContext->pop();
		$source = $runContext->pop()->asVar();
		if ($source->isContainer()) {
			$source = $source->getValue();
		} else {
			$source = new ListValue([$source]);
		}
		$iterator = $source->getIterator($valueTarget, $keyTarget, $indexTarget);
		$runContext->pushLoopChunk($op[1], $iterator);
		return true;
	}

	protected function opContinue() {
		$this->runContext->continueLoop();
		return true;
	}

	protected function opBreak() {
		$this->runContext->breakLoop();
		return true;
	}

	protected function breakIf($flag) {
		if (!$this->opTobool()) return false;

		if ($this->runContext->pop()->asVar()->getValue()->getRawValue() == $flag) {
			$this->runContext->breakLoop();
		}
		return true;
	}

	protected function opWhile() {
		return $this->breakIf(false);
	}

	protected function opUntil() {
		return $this->breakIf(true);
	}

	protected function opExit() {
		$this->runContext->finish();
		return true;
	}

	protected function opReturn() {
		$this->runContext->setOutput($this->runContext->peek()->value);
		$this->runContext->finish();
		return true;
	}

	protected function opDefault($op) {
		if (!$this->runContext->getTemplateFlag()) {
			$this->runContext->pushCodeChunk($op[1]);
		}
		return true;
	}
}
