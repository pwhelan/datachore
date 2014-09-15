<?php

/** @TODO:
*    * Allow setting keys directly with models and not with their keys, ie:
*      $object->ref = $ref instead of $object->ref = $ref->key.
*/
namespace Datachore;

class Model extends Datachore
{
	/** private super key **/
	protected $__key = null;
	
	/** Property definitions **/
	protected $properties = [];
	
	/** Property Values **/
	protected $values = [];
	
	/** Changed values **/
	protected $updates = [];
	
	/** Foreign Objects **/
	protected $foreign = [];
	
	
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
		else if ($this->properties[$key] instanceof Type\Key)
		{
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
			
			if (!isset($fkey) || !$fkey instanceof \google\appengine\datastore\v4\Key)
			{ 
				return null;
			}
			
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
		else if (isset($this->updates[$key]))
		{
			return $this->updates[$key];
		}
		
		if (isset($this->values[$key]))
		{
			return $this->values[$key]->rawValue();
		}
		
		if (isset($this->properties[$key]))
		{
			return null;
		}
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
		else if ($val instanceof \google\appengine\datastore\v4\Key)
		{
			return $this->updates[$key] = $val;
		}
		else if ($this->properties[$key] instanceof Type\Set && is_array($val))
		{
			return $this->updates[$key] = new \ArrayObject($val);
		}
		else if ($val instanceof Model)
		{
			$this->updates[$key] = $val->key;
			$this->foreign[$key] = $val;
			
			return $val;
		}
		
		if (!isset($this->properties[$key]))
		{
			throw new \Exception("Unknown Property for ".get_class($this).": ".$key);
		}
		
		return $this->updates[$key] = $val;
	}
	
	public function __isset($key)
	{
		return isset($this->values[$key]) || isset($this->updates[$key]);
	}
	
	public function getKey($key)
	{
		if (isset($this->properties[$key]) && $this->properties[$key] instanceof Type\Key)
		{
			if (isset($this->updates[$key]))
			{
				return $this->updates[$key];
			}
			else if (isset($this->values[$key]))
			{
				return $this->values[$key];
			}
			return null;
		}
		
		throw new \Exception('Unknown Key: '.$key);
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
			if (isset($this->updates[$key]) && !isset($this->foreign[$key]))
			{
				if ($this->properties[$key] instanceof Type\Timestamp)
				{
					$val = $this->updates[$key];
					switch(true)
					{
						case $val instanceof \DateTime:
							$val = $val->getTimestamp();
						case is_numeric($val):
							break;
						case is_string($val):
							$val = strtotime($val);
							break;
					}
					$ret[$key] = $val;
				}
				else
				{
					$ret[$key] = $this->updates[$key];
				}
			}
			else if (isset($this->foreign[$key]))
			{
				$ret[$key] = [
					'kind'	=> $this->foreign[$key]->key->getPathElement(0)->getKind(),
					'id'	=> $this->foreign[$key]->key->getPathElement(0)->getId()
				];
			}
			else if (isset($this->values[$key]))
			{
				if (isset($this->values[$key]))
				{
					$val = $this->values[$key]->rawValue();
					if ($val instanceof \google\appengine\datastore\v4\Key)
					{
						$ret[$key] = [
							'kind'	=> $val->getPathElement(0)->getKind(),
							'id'	=> $val->getPathElement(0)->getId()
						];
					}
					// Interim fix for timestamp values
					else if ($this->properties[$key] instanceof Type\Timestamp)
					{
						$ret[$key] = $val / (1000 * 1000);
					}
					else
					{
						$ret[$key] = $val;
					}
				}
			}
		}
		
		return $ret;
	}
	
	final public function __construct($entity = null)
	{
		parent::__construct();
		
		
		if ($entity)
		{
			$this->__key = $entity->entity->getKey();
			foreach($entity->entity->getPropertyList() as $property)
			{
				$this->values[$property->getName()] =
					new Value($property->getValue());
			}
		}
		
		foreach ($this->properties as $key => $property)
		{
			if (is_numeric($property))
			{
				$this->properties[$key] = Type::getTypeFromEnum($property);
			}
		}
		
		if (method_exists($this, 'define'))
		{
			$this->define();
		}
	}
	
}
