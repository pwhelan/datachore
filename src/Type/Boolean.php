<?php

namespace Datachore\Type;

class Boolean implements \Datachore\TypeInterface
{
	protected $_val = null;
	
	
	public function get()
	{
		return $this->_val;
	}
	
	public function set($value)
	{
		$this->_val = $value ? TRUE : FALSE;
	}
}
