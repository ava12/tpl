<?php

namespace ava12\tpl;


abstract class AbstractException extends \RuntimeException {

	protected static $messages = [];

	protected $data = [];

	public function getData() {
		return $this->data;
	}

	public function __construct($code, $data = null) {
		$data = (array)$data;
		$this->data = $data;
		$message = 'ошибка ' . $code . ': ' . static::$messages[$code];
		if ($data) $message = vsprintf($this->message, $data);

		parent::__construct($message, $code);
	}

	abstract public function getSourceId();
	abstract public function getSourceName();
	abstract public function getSourceLine();
	abstract public function getSourceColumn();
}