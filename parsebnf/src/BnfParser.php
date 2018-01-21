<?php

/*
 * Класс предназначен для обработки синтаксиса TPL, для более общих задач - никаких гарантий.
 * Все еще имеются ошибки. Например, конструкции вида {{foo}, [bar]} не распознаются
 * как ошибки и обрабатываются некорректно.
 */

require_once __DIR__ . '/BnfException.php';

class BnfParser {
	const TOKEN_TERMINAL = '*';
	const TOKEN_NON_TERMINAL = 'N';

	const VARIANT_GROUP = '&';
	const VARIANT_REPEAT = '*';
	const VARIANT_OPTIONAL = '?';

	const TOKEN_RE = '/(#[^\\r\\n]*)|("[^"]*"|\'[^\']*\')|([a-zA-Z_][a-zA-Z0-9_-]*)|([=,;|(){}[\]])|\\S/';

	protected $source = '';
	protected $sourcePos = 0;
	protected $savedToken = null;

	/* нетерминал => {влияющий_нетерминал => true} */
	protected $depends = [];

	/* нетерминал => {зависимый_нетерминал => true} */
	protected $affects = [];

	/* нетерминал => [терминал+]*/
	protected $firstTerminals = [];

	/*
	 нетерминал => список
	 список: [тип, [вариант+]+]
	 тип: "&"|"?"|"*"
	 вариант: терминал|=нетерминал|список
	*/
	protected $definitions = [];

	/* [нетерминал*] */
	protected $resolved = [];

	/*
	нетерминал => [{терминал => [индекс_состояния, нетерминал]|индекс_состояния|false}]
	false: последний терминал - завершить обработку
	специальные "терминалы":
	 "": "любой другой" - перейти к следующему состоянию (либо завершить)
	*/
	protected $grammar;


	public function __construct($source = '') {
		$this->source = $source;
	}

	public function parse() {
		if (isset($this->grammar)) return $this->grammar;

		$this->grammar = [];
		while ($this->readDefinition());
		$this->checkDefinitions();
		$this->buildGrammar();
		$this->checkDependencies();

		$this->source = null;
		return $this->grammar;
	}

	protected function tokenException($code, $data = []) {
		preg_match_all('/\\r\\n|\\r|\\n/', substr($this->source, 0, $this->sourcePos), $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		$line = count($matches) + 1;
		$lastLinePos = ($line == 1 ? 0 : $matches[$line - 2][0][1] + strlen($matches[$line - 2][0][0]));
		$col = mb_strlen(substr($this->source, $lastLinePos, $this->sourcePos - $lastLinePos)) + 1;
		return new BnfException($code, $data, $line, $col);
	}

	protected function tokenTypeName($type) {
		switch ($type) {
			case static::TOKEN_TERMINAL:
				return 'terminal';
			break;

			case static::TOKEN_NON_TERMINAL:
				return 'nonterminal';
			break;

			default:
				return '"' . $type . '"';
		}
	}

	protected function nextToken() {
		if ($this->savedToken) {
			$result = $this->savedToken;
			$this->savedToken = null;
			return $result;
		}

		while (preg_match(static::TOKEN_RE, $this->source, $matches, PREG_OFFSET_CAPTURE, $this->sourcePos)) {
			$this->sourcePos = $matches[0][1] + strlen($matches[0][0]);
			if (isset($matches[4])) return [$matches[4][0], $matches[4][0]];
			if (isset($matches[3])) return [static::TOKEN_NON_TERMINAL, $matches[3][0]];
			if (isset($matches[2])) return [static::TOKEN_TERMINAL, substr($matches[2][0], 1, -1)];

			if (isset($matches[1])) continue;

			$char = $matches[0][0];
			if ($char == '"' or $char == '\'') {
				throw $this->tokenException(BnfException::ERR_QUOTE);
			} else {
				throw $this->tokenException(BnfException::ERR_CHAR, $matches[0][0]);
			}
		}

		return null;
	}

	protected function getToken($types, $panic = true) {
		$token = $this->nextToken();
		if (!$token) {
			if ($panic) throw $this->tokenException(BnfException::ERR_EOF);
			else return null;
		}

		$types = (array)$types;
		if (in_array($token[0], $types)) {
			return $token;

		} else {
			if ($panic) {
				$typeName = $this->tokenTypeName($token[0]);
				$expectedName = $this->tokenTypeName($types[0]);
				throw $this->tokenException(BnfException::ERR_TOKEN, [$typeName, $expectedName]);
			} else {
				$this->putToken($token);
				return false;
			}
		}
	}

	protected function putToken($token) {
		$this->savedToken = $token;
	}

	protected function addDependency($depend, $affect) {
		if (!isset($this->depends[$depend])) $this->depends[$depend] = [];
		$this->depends[$depend][$affect] = true;

		if (!isset($this->affects[$affect])) $this->affects[$affect] = [];
		$this->affects[$affect][$depend] = true;
	}

	/*
	 [тип, [вариант+]+]
	 тип: "&"|"?"|"*"
	 вариант: терминал|=нетерминал|список
	 обновляет зависимости
	*/
	protected function readList($nonTerminal = 0, $listType = null) {
		$expected = [
			[static::TOKEN_TERMINAL, static::TOKEN_NON_TERMINAL, '(', '[', '{'],
			[',', '|'],
		];

		$listType = ($listType ?: static::VARIANT_GROUP);
		$result = [$listType];
		$variants = [];
		$gotItem = 0;

		while (true) {
			$token = $this->getToken($expected[$gotItem], !$gotItem);
			if (!$token) break;

			$value = $token[1];
			$gotItem = 1 - $gotItem;

			switch ($token[0]) {
				case static::TOKEN_TERMINAL:
					$variants[] = $value;
				break;

				case static::TOKEN_NON_TERMINAL:
					$this->addDependency($nonTerminal, $value);
					$variants[] = '= ' . $value;
				break;

				case '(':
					$variants[] = $this->readList($nonTerminal, static::VARIANT_GROUP);
					$this->getToken(')');
				break;

				case '{':
					$variants[] = $this->readList($nonTerminal, static::VARIANT_REPEAT);
					$this->getToken('}');
				break;

				case '[':
					$variants[] = $this->readList($nonTerminal, static::VARIANT_OPTIONAL);
					$this->getToken(']');
				break;

				case ',':
					$result[] = $variants;
					$variants = [];
				break;

				case '|':
				break;
			}
		}

			if ($variants) $result[] = $variants;
			return $result;
	}

	protected function readDefinition() {
		$token = $this->getToken(static::TOKEN_NON_TERMINAL, false);
		if (!$token) return false;

		$name = $token[1];
		if (!empty($this->definitions[$name])) {
			throw $this->tokenException(BnfException::ERR_DEFINED, $name);
		}

		if (empty($this->definitions)) $name = 0;
		$this->getToken('=');
		$this->depends[$name] = [];
		$this->definitions[$name] = $this->readList($name);
		$this->getToken(';');
		return true;
	}

	protected function detectFirstTerminals($definition) {
		$result = [];
		$index = 1;

		do {
			$repeat = false;
			foreach ($definition[$index] as $variant) {
				if (is_array($variant)) {
					$repeat |= ($variant[0] <> static::VARIANT_GROUP);
					$variant = $this->detectFirstTerminals($variant);
					if ($variant) $result += $variant;
					else return false;

				} elseif (substr($variant, 0, 2) <> '= ') {
					$result[$variant] = true;
				} else {
					return false;
				}
			}

			$index++;
		} while ($repeat and $index < count($definition));

		return $result;
	}

	protected function checkDefinitions() {
		if (!$this->definitions) throw new BnfException(BnfException::ERR_EMPTY);

		$missing = array_diff(array_keys($this->affects), array_keys($this->definitions));
		if ($missing) {
			throw new BnfException(BnfException::ERR_MISSING, [$missing]);
		}

		foreach (array_keys($this->depends) as $nonTerminal) {
			$terminals = $this->detectFirstTerminals($this->definitions[$nonTerminal]);
			if (!$terminals) continue;

			$this->firstTerminals[$nonTerminal] = array_keys($terminals);
			if (isset($this->affects[$nonTerminal])) {
				foreach (array_keys($this->affects[$nonTerminal]) as $affected) {
					unset($this->depends[$affected][$nonTerminal]);
				}
			}
			unset($this->affects[$nonTerminal]);
		}

		foreach (array_keys($this->depends) as $nonTerminal) {
			if (!$this->depends[$nonTerminal]) {
				$this->resolved[] = $nonTerminal;
				unset($this->depends[$nonTerminal]);
			}
		}

		array_reverse(array_unique($this->resolved));
	}

	protected function getFirstTerminals($rule) {
		$result = [];
		$stateIndex = 0;
		while (true) {
			$state = $rule[$stateIndex];
			$result += $state;
			if (!empty($state[''])) $stateIndex = $state[''];
			else break;
		}

		unset($result['']);
		return array_keys($result);
	}

	protected function buildGroupRule($nonTerminal, &$rule, $group, $enterState, $exitState) {
		$isRepeated = ($group[0] == static::VARIANT_REPEAT);
		$isOptional = ($group[0] <> static::VARIANT_GROUP);
		$currentState = $enterState;
		$itemCount = count($group) - 1;

		if ($isOptional) $rule[$currentState][''] = $exitState;
		$nextState = ($itemCount > 1 ? count($rule) : ($isRepeated ? $enterState : $exitState));

		for ($itemIndex = 1; $itemCount > 0; $itemIndex++, $itemCount--) {
			if ($nextState and !isset($rule[$nextState])) {
				$rule[$nextState] = [];
			}
			foreach($group[$itemIndex] as $variant) {
				if (is_array($variant)) {
					$this->buildGroupRule($nonTerminal, $rule, $variant, $currentState, $nextState);
				} else {
					$isNonTerminal = (substr($variant, 0, 2) == '= ');
					if ($isNonTerminal) {
						$variant = substr($variant, 2);
						$terminals = $this->firstTerminals[$variant];
					} else {
						$terminals = [$variant];
					}

					foreach ($terminals as $terminal) {
						if (isset($rule[$currentState][$terminal])) {
							throw new BnfException(BnfException::ERR_UNDECIDABLE, [$nonTerminal, $terminal]);
						}

						$rule[$currentState][$terminal] = ($isNonTerminal ? [$nextState, $variant] : $nextState);
					}
				}
			}

			$currentState = $nextState;
			if ($itemCount == 2) {
				$nextState = ($isRepeated ? $enterState : $exitState);
			} else {
				$nextState = count($rule);
			}
		}
	}

	protected function buildRule($nonTerminal, $definition) {
		$rule = [[]];
		$this->buildGroupRule($nonTerminal, $rule, $definition, 0, false);
		return $rule;
	}

	protected function buildGrammar() {
		while($this->resolved) {
			$nonTerminal = array_shift($this->resolved);
			$rule = $this->buildRule($nonTerminal, $this->definitions[$nonTerminal]);
			$this->grammar = [$nonTerminal => $rule] + $this->grammar;
			$this->firstTerminals[$nonTerminal] = $this->getFirstTerminals($rule);

			if (!empty($this->affects[$nonTerminal])) {
				foreach (array_keys($this->affects[$nonTerminal]) as $affected) {
					unset($this->depends[$affected][$nonTerminal]);
					if (!$this->depends[$affected]) {
						unset($this->depends[$affected]);
						$this->resolved[] = $affected;
					}
				}
			}
			unset($this->affects[$nonTerminal]);
		}

		$this->grammar = [$this->grammar[0]] + $this->grammar;
	}

	protected function checkDependencies() {
		$unsolved = array_keys($this->depends);
		if ($unsolved) {
			throw new BnfException(BnfException::ERR_NOTERM, [$unsolved]);
		}
	}
}
