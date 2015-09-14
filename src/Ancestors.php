<?php

/**
 * Array wrapper to both allow for property-based access to Ancestors for
 * writing during entity creation as well as enforcing read-only status
 * for objects that have already been created that have ancestors.
 */
namespace Datachore;
use \google\appengine\datastore\v4\Key as GoogleKey;
use \google\appengine\datastore\v4\PathElement as GooglePathElement;

class Ancestors extends \ArrayObject
{
	protected $read_only = false;
	
	
	public function offsetSet($index, $newval)
	{
		if ($this->read_only)
		{
			throw new \InvalidArgumentException("Cannot modify ancestors once an entity is already created");
		}
		
		if ($index && (!is_numeric($index) || ($index != (int)$index)))
		{
			throw new \InvalidArgumentException("only numeric indexes are allowed: {$index}");
		}
		
		if ($newval instanceof Model)
		{
			$newval = $newval->key->getPathElement(
				$newval->key->getPathElementSize()-1
			);
		}
		else if (!$newval instanceof GooglePathElement)
		{
			throw new \InvalidArgumentException("Illegal value for ancestor");
		}
		
		parent::offsetSet($index, $newval);
	}
	
	public function __construct(GoogleKey $key = null)
	{
		if ($key)
		{
			for ($i = 0; $i < $key->getPathElementSize() - 1; $i++)
			{
				$this->offsetSet($i, $key->getPathElement($i));
			}
			$this->read_only = true;
		}
	}
}
