<?php

namespace ava12\tpl\machine;

interface IVarContainer {
	/**
	 * @param int $index
	 * @return Variable|null
	 */
	public function getByIndex($index);

	/**
	 * @param string $key
	 * @return Variable|null
	 */
	public function getByKey($key);

	/**
	 * @param int $index
	 * @param Variable $var
	 */
	public function setByIndex($index, $var);

	/**
   * @param string $key
   * @param Variable $var
   */
	public function setByKey($key, $var);

	/**
	 * @return bool
	 */
	public function isPublic();
}
