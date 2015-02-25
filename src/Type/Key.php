<?php

namespace Datachore\Type;

use \Datachore\Model;


class Key implements \Datachore\TypeInterface
{
	protected $_key = null;
	protected $_entity = null;
	
	
	public function get()
	{
		if ($this->_entity)
		{
			return $this->_entity;
		}
		else if ($this->_key)
		{
			$kindName = $this->_key->getPathElement(0)->getKind();
			$className = str_replace('_', '\\', $kindName);
			
			$entity = (new $className)
				->where('id', '==', $this->_key)
				->first();
			
			if ($entity)
			{
				$this->_entity = $entity;
				return $entity;
			}
			
		}
		
		return false;
	}
	
	public function set($value)
	{
		switch (true)
		{
			case $value instanceof \google\appengine\datastore\v4\Key:
				$this->_key = $value;
				return;
			
			case $value instanceof Model:
				$this->_entity = $value;
				$this->_key = $value->key;
				return;
			
			case $value === NULL:
				$this->_key = $this->_entity = null;
				return;
			
			default:
				throw new \InvalidArgumentException(
					"Unsupported Key Value Type"
				);
		}
	}
	
	public function key()
	{
		if ($this->_key == null)
		{
			return null;
		}
		
		return clone $this->_key;
	}
}
