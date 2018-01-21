<?php

namespace ava12\tpl;

abstract class AbstractException extends \RuntimeException {

	protected static $messages = [];

	protected $data = [];

	public function getData() {
		return $this->data;
	}

	protected function init($code, $data) {
		$data = (array)$data;
		$this->code = $code;
		$this->data = $data;
		$this->message = 'ошибка ' . $code . ': ' . static::$messages[$code];
		if ($data) $this->message = vsprintf($this->message, $data);
	}

	abstract public function getSourceId();
	abstract public function getSourceName();
	abstract public function getSourceLine();
	abstract public function getSourceColumn();
}