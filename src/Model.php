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
					$fkey = $this->values[$key];
				}
			}
			
			if (!isset($fkey) && isset($this->values[$key]))
			{
				$fkey = $this->values[$key]->rawValue();
			}
			
			if (!isset($fkey))
			{
				return null;
			}
			
			if (!isset($this->foreign[$key]))
			{
				$kindName = $fkey->getPathElement(0)->getKind();
				$className = str_replace('_', '\\', $kindName);
				
				$this->foreign[$key] = (new $className)
						->where('id', '==', $fkey)
					->first();
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
		if (($key == 'id' || $key == 'key') && $val instanceof \google\appengine\datastore\v4\Key)
		{
			return $this->__key = $val;
		}
		else if ($val instanceof \google\appengine\datastore\v4\Key)
		{
			return $this->updates[$key] = $val;
		}
		else if ($val instanceof Model)
		{
			$this->updates[$key] = $val->key;
			return $this->foreign[$key] = $val;
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
				$ret[$key] = $this->updates[$key];
			}
			else if (isset($this->values[$key]))
			{
				if (isset($this->values[$key]))
				{
					$ret[$key] = $this->values[$key]->rawValue();
				}
				else
				{
					$ret[$key] = $this->values[$key];
				}
			}
			else if (isset($this->foreign[$key]))
			{
				$ret[$key] = [
					'kind'	=> $this->foreign[$key]->key->getPathElement(0)->getKind(),
					'id'	=> $this->foreign[$key]->key->getPathElement(0)->getId()
				];
			}
		}
		
		return $ret;
	}
	
	public function mergeIntoEntity($entity)
	{
		foreach($this->properties as $key => $type)
		{
			if (isset($this->updates[$key]))
			{
				$value = $this->updates[$key];
			}
			else
			{
				$value = $this->values[$key];
			}
			
			$property = $entity->addProperty();
			$propval = $property->mutableValue();
			
			
			switch(true)
			{
				case $this->properties[$key] instanceof Type\String:
					$propval->setStringValue($value);
					break;
				case $this->properties[$key] instanceof Type\Integer:
					$propval->setIntegerValue($value);
					break;
				case $this->properties[$key] instanceof Type\Boolean:
					$propval->setBooleanValue($value);
					break;
				case $this->properties[$key] instanceof Type\Double:
					$propval->setDoubleValue($value);
					break;
				case $this->properties[$key] instanceof Type\Timestamp:
					
					switch(true)
					{
						case $value instanceof \DateTime:
							$time = $value->format('u') * (1000 * 1000) +
								$value->getTimestamp() * (1000 * 1000);
							break;
						case is_numeric($value):
							$time = (int)($value * 10000) * 100;
							break;
						case is_string($value):
							strtotime($value) * (1000 * 1000);
							break;
					}
					
					$propval->setTimestampMicrosecondsValue($time);
					break;
				case $this->properties[$key] instanceof Type\Blob:
					$propval->setBlobValue($value);
					break;
				case $this->properties[$key] instanceof Type\BlobKey:
					$propval->setBlobKeyValue($value);
					break;
				case $this->properties[$key] instanceof Type\Key:
					if ($value)
					{
						if ($value instanceof \google\appengine\datastore\v4\Key)
						{
							$keyval = $propval->mutableKeyValue();
							$keyval->mergeFrom($value);
						}
						else if ($value instanceof Model)
						{
							$this->_GoogleKeyValue($propval->mutableKeyValue(), $value);
						}
					}
					break;
				
				default:
					throw new \Exception("ILLEGAL ARGZZZZ!");
			}
			
			$property->setName($key);
		}
	}
	
	final public function __construct($entity = null)
	{
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
