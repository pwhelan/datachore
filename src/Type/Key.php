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
		if (isset($this->updates[$key]))
		{
			if ($this->updates[$key] instanceof \google\appengine\datastore\v4\Key)
			{
				$fkey = $this->updates[$key];
			}
		}
		
		if (!isset($fkey) && isset($this->values[$key]))
		{
			$fkey = $this->values[$key]->rawValue();
		}
		
		// @codeCoverageIgnoreStart
		if (!isset($fkey) || !$fkey instanceof \google\appengine\datastore\v4\Key)
		{
			return null;
		}
		// @codeCoverageIgnoreEnd
		
		if (!isset($this->foreign[$key]))
		{
			$kindName = $fkey->getPathElement(0)->getKind();
			$className = str_replace('_', '\\', $kindName);
			
			$model = (new $className)->where('id', '==', $fkey)->first();
			if ($model)
			{
				$this->foreign[$key] = $model;
				return $model;
			}
		}
		
		return $this->foreign[$key];
	}
	
	public function set($value)
	{
		switch (true)
		{
			case $value instanceof \google\appengine\datastore\v4\Key:
				$this->_key = $value;
				return;
			
			case $value instanceof \Datachore\Type\Key:
				$this->_key = $value->key();
				return;
			
			case $value instanceof Model:
				$this->_entity = $value;
				$this->_key = $value->key;
				return;
			
			case $value === NULL:
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
