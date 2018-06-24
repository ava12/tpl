<?php

namespace ava12\tpl\machine;

trait ObjectCounter {
	protected static $lastIndex = 0;
	protected $objectIndex;

	protected function newIndex() {
		static::$lastIndex++;
		$this->objectIndex = static::$lastIndex;
	}

	public function getObjectIndex() {
		return $this->objectIndex;
	}
}
