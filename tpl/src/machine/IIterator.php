<?php

namespace ava12\tpl\machine;

interface IIterator {

	/**
	 * @return int общее количество итераций
	 */
	public function count();

	/**
	 * true: обработан очередной элемент контейнера, false: элементов больше нет
	 *
	 * @return bool
	 */
	public function next();
}
