<?php

namespace Datachore\Type;

class Set implements \Datachore\TypeInterface
{
	protected $_val = null;
	protected $_type = null;
	
	
	public function type($type = null)
	{
		if ($this->_type == null && $type)
		{
			if ($type instanceof \Datachore\TypeInterface)
			{
				$this->_type = $type;
			}
			else
			{
				$this->_type = \Datachore\Type::getTypeFromEnum($type);
			}
		}
		return $this->_type;
	}
	
	public function get()
	{
		// Must allocate a new Datachore\Collection here since sets are modified
		// indirectly and externally.
		if ($this->_val === null)
		{
			$this->_val = new \Datachore\Collection;
		}
		
		return $this->_val;
	}
	
	public function set($value)
	{
		if ($value == null)
		{
		}
		else if ($value instanceof \Datachore\Collection)
		{
			$this->_val = clone $value;
		}
		else if ($value instanceof Set)
		{
			$this->_val = clone $value->get();
		}
		else if (is_array($value))
		{
			$this->_val = new \Datachore\Collection;
			foreach ($value as $v)
			{
				$this->_val[] = $v;
			}
		}
		else
		{
			throw new \InvalidArgumentException("Invalid Type for Set: ".get_class($value));
		}
	}
}
