<?php

namespace Datachore\Type;

class String implements \Datachore\TypeInterface
{
	protected $_val = null;
	
	
	public function get()
	{
		return $this->_val;
	}
	
	public function set($value)
	{
		// Attempt to coherce value into a string, copied code from SO:
		// http://stackoverflow.com/a/5496674
		if (
			is_string($value) || 
			(
				( !is_array( $value ) ) &&
				(
					( !is_object( $value ) && 
					settype( $value, 'string' ) !== false ) ||
					(
						is_object( $value ) && 
						method_exists( $value, '__toString' )
					)
				)
			)
		)
		{
			$this->_val = (string)$value;
			settype($value, 'string');
		}
		else
		{
			throw new \InvalidArgumentException("Value is not a String");
		}
	}
}
