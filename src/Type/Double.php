<?php

namespace Datachore\Type;

class Double implements \Datachore\TypeInterface
{
	protected $_val = null;
	
	
	public function get()
	{
		return $this->_val;
	}
	
	public function set($value)
	{
		if (!is_numeric($value))
		{
			$oldvalue = $value;
			if (settype($value, "float") === FALSE)
			{
				throw new \InvalidArgumentException(
					"Value is non-numeric"
				);
			}
			
			if ($oldvalue != $value)
			{
				throw new \InvalidArgumentException(
					"Value is non-numeric"
				);
			}
		}
		
		$this->_val = $value;
	}
}
