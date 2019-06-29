<?php

namespace ava12\tpl\lib;

use \ava12\tpl\env\Env;

class Loader {
	/** @var Env */
	protected $env;
	/** @var ILib[] */
	public $libs = []; // {имя: библиотека}

	public function init(Env $env) {
		$this->env = $env;

		foreach ($this->env->config->lib->lib as $name => $lib) {
			$this->addLib($name, $lib);
		}
	}

	public function addLib($name, $lib) {
		if (!isset($this->libs[$name])) {
			if (!is_object($lib)) {
				$lib = call_user_func([$lib, 'setup'], $this->env);
			}
			$this->libs[$name] = $lib;
		}
	}
}
