<?php

namespace ava12\tpl\machine;

interface IFunctionValue extends IValue {
	public function isPure();

	/**
	 * @param Context $context
	 * @param IListValue|null $container
	 * @param IListValue|null $args
	 * @return Variable|null
	 */
	public function call($context, $container = null, $args = null);
}
