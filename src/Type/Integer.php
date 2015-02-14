<?php

namespace Datachore\Type;

class Integer implements \Datachore\TypeInterface
{
	protected $_val = null;
	
	
	public function get()
	{
		return $this->_val;
	}
	
	public function set($value)
	{
		if (!is_integer($value))
		{
			$oldvalue = $value;
			if (settype($value, "integer") === FALSE)
			{
				throw new \InvalidArgumentException(
					"Value is non-numeric"
				);
			}
			
			if ($oldvalue != $value)
			{
				throw new \InvalidArgumentException(
					"Value is non-numeric or has lost ".
					"precision (non-integer)"
				);
			}
		}
		
		$this->_val = $value;
	}
}
