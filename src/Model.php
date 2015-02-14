<?php

/** Base Model class for GAE Datastore Datachore API.
 * 
 * This class is the base class used to represent entities in 
 * Google Appengine's Datastore. Extend this class to represent a set of
 * Datastore entities in your own application. The type of each field or
 * property must be modeled using one of the Datachore\Type classes.
 */
namespace Datachore;

class Model extends Datachore
{
	/** private super key **/
	protected $__key = null;
	
	/** Property definitions **/
	protected $properties = [];
	
	/** Changed values **/
	protected $updated = [];
	
	
	public function __get($key)
	{
		if ($key == 'id')
		{
			if ($this->__key)
			{
				return $this->__key->getPathElement(0)->getId();
			}
			else
			{
				return null;
			}
		}
		else if ($key == 'key')
		{
			return $this->__key;
		}
		else if (isset($this->properties[$key]))
		{
			return $this->properties[$key]->get();
		}
		
		throw new \InvalidArgumentException("Unknown property: ".$key);
	}
	
	public function __set($key, $val)
	{
		if (($key == 'id' || $key == 'key'))
		{
			if ($val instanceof \google\appengine\datastore\v4\Key)
			{
				return $this->__key = clone $val;
			}
		}
		else if (isset($this->properties[$key]))
		{
			$this->properties[$key]->set($val);
			$this->updated[] = $key;
		}
		else
		{
			throw new \Exception("Unknown Property for ".get_class($this).": ".$key);
		}
	}
	
	public function __isset($key)
	{
		return isset($this->properties[$key]);
	}
	
	public function getKey($key)
	{
		if (isset($this->properties[$key]) && $this->properties[$key] instanceof Type\Key)
		{
			return $this->properties[$key]->key();
		}
		
		throw new \Exception('Unknown Key: '.$key);
	}
	
	private function _valueToArray($prop)
	{
		switch(true)
		{
			case $prop == null || $prop->get() == null:
				return null;
			
			case $prop instanceof Type\Timestamp:
				return $prop->get()->getTimestamp();
			
			case $prop instanceof Type\Key:
				$key = $prop->key();
				return [
					'id'	=> $key->getPathElement(0)->getId(),
					'kind'	=> $key->getPathElement(0)->getKind()
				];
			
			case $prop instanceof Type\Set:
				return $prop->get()
					->map(function($item) {
						return $this->_valueToArray($item);
					})
					->toArray();
			
			case $prop instanceof TypeInterface:
				return $prop->get();
			
			case $prop instanceof Model:
				$key = $prop->key;
				return [
					'id'	=> $key->getPathElement(0)->getId(),
					'kind'	=> $key->getPathElement(0)->getKind()
				];
			
			default:
				return $prop;
		}
	}
	
	public function toArray()
	{
		$ret = [];
		
		
		if (isset($this->__key))
		{
			$ret['id'] = $this->__key->getPathElement(0)->getId();
		}
		
		foreach ($this->properties as $key => $prop)
		{
			$ret[$key] = $this->_valueToArray($prop);
		}
		
		return $ret;
	}
	
	final public function __construct($entity = null)
	{
		parent::__construct();
		
		
		foreach ($this->properties as $key => $property)
		{
			if (is_numeric($property))
			{
				$this->properties[$key] = Type::getTypeFromEnum($property);
			}
		}
		
		if ($entity)
		{
			$this->__key = $entity->entity->getKey();
			foreach($entity->entity->getPropertyList() as $property)
			{
				$value = new Value($property->getValue());
				$raw = $value->rawValue();
				
				if ($this->properties[$property->getName()] instanceof Type\Timestamp)
				{
					$raw /= (1000 * 1000);
				}
				
				$this->properties[$property->getName()]->set($raw);
				//);
			}
		}
		
		// @codeCoverageIgnoreStart
		if (method_exists($this, 'define'))
		{
			$this->define();
		}
		// @codeCoverageIgnoreEnd
	}
	
}
