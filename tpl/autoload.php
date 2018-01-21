<?php

spl_autoload_register(function ($className) {
	if (substr($className, 0, 10) == 'ava12\\tpl\\') {
		$path = __DIR__ . DIRECTORY_SEPARATOR . 'src' .
			str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 9)) . '.php';
		if (file_exists($path)) require_once $path;
	}
});
