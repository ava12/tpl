<?php

namespace ava12\tpl\parser;

abstract class AbstractStateHandler {
	/** @var Parser */
	protected $parser;
	protected $nonTerminal;

	/**
	 * @param Parser $parser
	 * @param string $nonTerminal
	 */
	public function __construct($parser, $nonTerminal) {
		$this->parser = $parser;
		$this->nonTerminal = $nonTerminal;
		$this->init();
	}

	protected function init() {}

	/**
	 * @param Token $token
	 */
	public function useToken($token) {}

	/**
	 * @param string $nonTerminal
	 */
	public function preReport($nonTerminal) {}

	/**
	 * @param string $nonTerminal
	 */
	public function postReport($nonTerminal) {}

	/**
	 */
	public function finish() {}
}
