<?php

namespace Datachore\Type;

class Timestamp implements \Datachore\TypeInterface
{
	static protected $_gmt = null;
	protected $_val = null;
	
	
	public function __construct()
	{
		if (self::$_gmt == null)
		{
			self::$_gmt = new \DateTimeZone('GMT');
		}
	}
	
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
				$this->_val = new \DateTime('@' . (string)$value, self::$_gmt);
				break;
			
			case is_string($value):
				$this->_val = new \DateTime($value, self::$_gmt);
				break;
			
			case $value == null:
				break;
			
			default:
				throw new \InvalidArgumentException(
					"Invalid Date Format"
				);
				break;
		}
	}
}
