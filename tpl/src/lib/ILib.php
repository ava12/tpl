<?php

namespace ava12\tpl\lib;

interface ILib {
	/**
	 * @param \ava12\tpl\env\Env $env
	 * @return ILib
	 */
	public static function setup(\ava12\tpl\env\Env $env);
}
