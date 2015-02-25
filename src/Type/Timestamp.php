<?php

namespace Datachore\Type;

class Timestamp implements \Datachore\TypeInterface
{
	protected $_val = null;
	
	
	public function get()
	{
		return $this->_val;
	}
	
	public function set($value)
	{
		switch (true)
		{
			case $value instanceof \DateTime:
				$this->_val = $value;
				break;
			
			case is_numeric($value):
				$this->_val = new \DateTime('@' . (string)$value);
				break;
			
			case is_string($value):
				$this->_val = new \DateTime($value);
				break;
			
			case $value == null:
				$this->_val = null;
				break;
			
			default:
				throw new \InvalidArgumentException(
					"Invalid Date Format"
				);
				break;
		}
	}
}
