<?php


/** @TODO:
 *    * Get NULL key references to save correctly.
 */
namespace Datachore;

class Datachore
{
	private $_datasetId = null;
	private $_datastore = null;
	
	protected $_operation;
	protected $_runQuery;
	protected $_query;
	protected $_filter;
	
	
	private function _clear()
	{
		$this->_runQuery = $this->datastore()->Factory('RunQueryRequest');
		$this->_query = $this->_runQuery->mutableQuery();
	}
	
	public function __construct()
	{
		$this->_clear();
	}
	
	public function setDatastore(Datastore $datastore)
	{
		$this->_datastore = $datastore;
		$this->setDatasetId($datastore->getDatasetId());
	}
	
	public function setDatasetId($datasetId)
	{
		$this->_datasetId = $datasetId;
	}
	
	public function datasetId()
	{
		return $this->_datasetId;
	}
	
	private function _kind_from_class($className = NULL)
	{
		if (!$className)
		{
			$className = get_class($this);
		}
		
		$kindName = str_replace('\\', '_', $className);
		return $kindName;
	}
	
	public function datastore()
	{
		if ($this->_datastore == null)
		{
			$this->setDatastore(Datastore::getInstance());
		}
		
		return $this->_datastore;
	}
	
	public function startSave()
	{
		$transactionRequest = $this->datastore()->Factory('BeginTransactionRequest');
		$transactionRequest->setCrossGroup(true);
		//$isolationLevel->setIsolationLevel('snapshot');
		
		$transaction = $this->datastore()->beginTransaction(
			$this->datasetId(),
			$transactionRequest
		);
	
		$commit = $this->datastore()->Factory('CommitRequest');
		$commit->setTransaction($transaction->getTransaction(2));
		$commit->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::TRANSACTIONAL);
		
		$mutation = $commit->mutableDeprecatedMutation();
		
		return [$commit, $mutation];
	}
	
	public function endSave($commit, $mutation, $collection = null)
	{
		$rc = $this->datastore()->commit($this->datasetId(), $commit);
		
		if ($collection)
		{
			$insertsIds = $mutation->getInsertAutoIdList();
			for ($i = 0; $i < count($insertIds); $i++)
			{
				foreach ($collection as $model)
				{
					if ($model->_opertion == $insertIds[$i])
					{
						$rc->getDeprecatedMutationResult()
							->getInsertAutoIdKey($i);
					}
				}
			}
		}
		else
		{
			if (!$this->id)
			{
				$this->__key = $rc->getDeprecatedMutationResult()
					->getInsertAutoIdKey(0);
			}
		}
		
		return $rc;
	}
	
	public function save($mutation = null)
	{
		if (!$mutation)
		{
			list($commit, $mutation) = $this->startSave();
		}
		
		
		if ($this->id)
		{
			$entity = $mutation->addUpdate();
			$this->_GoogleKeyValue($entity->mutableKey(), $this->id);
		}
		else
		{
			//$mutation->setOp(\google\appengine\datastore\v4\Mutation\Operation::INSERT);
			//$this->_GoogleKeyValue($mutation->mutableKey());
			
			$entity = $mutation->addInsertAutoId();
			$this->_GoogleKeyValue($entity->mutableKey());
		}
		
		
		$this->_operation = $entity;
		
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
		
		
		if (isset($commit))
		{
			$this->endSave($commit, $mutation);
		}
		
		return true;
	}
	
	
	const WHERE_LT = 1;
	const WHERE_LTEQ = 2;
	const WHERE_GT = 3;
	const WHERE_GTEQ = 4;
	const WHERE_EQ = 5;
	const WHERE_HAS_ANCESTOR = 11;
	
	
	private $_operator_strings = [
		self::WHERE_EQ		=> 'equal',
		self::WHERE_LT		=> 'lessThan',
		self::WHERE_LTEQ	=> 'lessThanOrEqual',
		self::WHERE_GT		=> 'greaterThan',
		self::WHERE_GTEQ	=> 'greaterThanOrEqual'
	];
	
	
	final protected function _GoogleKeyValue(\google\appengine\datastore\v4\Key $key, $id = null)
	{
		$partitionId = $key->mutablePartitionId();
		$path = $key->addPathElement();
		
		$partitionId->setDatasetId($this->datasetId());
		
		if ($id)
		{
			if (!is_object($id))
			{
				$path->setId($id);
			}
			else
			{
				if ($id instanceof Value)
				{
					$id = $id->rawValue();
				}
				$path->setId($id->getPathElement(0)->getId());
			}
		}
		
		$path->setKind($this->_kind_from_class());
		
		return $key;
	}
	
	private function _where($propertyName, $chain = 'and', $operatorEnum = -1, $rawValue = null)
	{
		if (!$this->_query->hasFilter() || !isset($this->filter))
		{
			$filter = $this->_query->mutableFilter();
			$this->_filter = $filter->mutableCompositeFilter();
			
			// Only choice for now
			$this->_filter->setOperator(\google\appengine\datastore\v4\CompositeFilter\Operator::AND_);
		}
		
		if (is_callable($propertyName))
		{
			$propertyName($this);
			return $this;
		}
		
		$filter = $this->_filter->addFilter();
		$propFilter = $filter->mutablePropertyFilter();
		
		$propRef = $propFilter->mutableProperty();
		$value = $propFilter->mutableValue();
		
		
		if ($rawValue instanceof Model)
		{
			$keyValue = $value->mutableKeyValue();
			$keyValue->mergeFrom($rawValue->key);
		}
		else if ($rawValue instanceof \google\appengine\datastore\v4\Key)
		{
			$keyValue = $value->mutableKeyValue();
			$keyValue->mergeFrom($rawValue);
		}
		else if ($propertyName == 'id' || $this->properties[$propertyName] instanceof Type\Key)
		{
			$this->_GoogleKeyValue($value->mutableKeyValue(), $rawValue);
		}
		else
		{
			switch(true)
			{
				case $this->properties[$propertyName] instanceof Type\Blob:
					$value->setBlobValue($rawValue);
					break;
					
				case $this->properties[$propertyName] instanceof Type\BlobKey:
					$value->setBlobKeyValue($rawValue);
					break;
					
				case $this->properties[$propertyName] instanceof Type\String:
					$value->setStringValue($rawValue);
					break;
					
				case $this->properties[$propertyName] instanceof Type\Boolean:
					$value->setBooleanValue($rawValue);
					break;
					
				case $this->properties[$propertyName] instanceof Type\Integer:
					$value->setIntegerValue($rawValue);
					break;
					
				case $this->properties[$propertyName] instanceof Type\Double:
					$value->setDoubleValue($rawValue);
					break;
					
				case $this->properties[$propertyName] instanceof Type\Timestamp:
					
					switch(true)
					{
						case $rawValue instanceof \DateTime:
							$time = $rawValue->format('u') * (1000 * 1000) +
								$rawValue->getTimestamp() * (1000 * 1000);
							break;
						case is_numeric($rawValue):
							$time = (int)($rawValue * 10000) * 100;
							break;
						case is_string($rawValue):
							strtotime($rawValue) * (1000 * 1000);
							break;
					}
					
					$value->setTimestampMicrosecondsValue($time);
					break;
			}
			
			//$value->setStringValue($rawValue);
		}
		
		if ($propertyName == 'id')
		{
			$propRef->setName('__key__');
		}
		else
		{
			$propRef->setName($propertyName);
		}
		
		$propFilter->setOperator($operatorEnum);
		return $this;
	}
	
	public static function all()
	{
		$_class = get_called_class();
		$instance = new $_class;
		return $instance->get();
	}
	
	public function first()
	{
		$this->_query->setLimit(1);
		$collection = $this->get();
		
		return $collection->first();
	}
	
	public function get()
	{
		$kind = $this->_query->addKind();
		$kind->setName($this->_kind_from_class());
		$partition_id = $this->_runQuery->mutablePartitionId();
		$partition_id->setDatasetId($this->datasetId());
		
		$results = $this->datastore()->runQuery($this->datasetId(), $this->_runQuery);
		$this->_clear();
		
		
		$collection = new Collection;
		foreach($results->getBatch()->getEntityResultList() as $result)
		{
			$collection[] = new static($result);
		}
		
		return $collection;
	}
	
	public static function find($id)
	{
		$_class = get_called_class();
		$instance = new $_class;
		
		return $instance->where('id', '==', $id)->first();
	}
	
	private static function _isWhere($func)
	{
		$ifunc = strtolower($func);
		return substr($ifunc, 0, 5) == 'where' ||
			substr($ifunc, 0, 7) == 'orwhere' ||
			substr($ifunc, 0, 8) == 'andwhere';
	}
	
	public function __call($func, $args)
	{
		$ifunc = strtolower($func);
		
		
		if (self::_isWhere($func))
		{
			if (substr($ifunc, 0, 7) == 'orwhere')
			{
				$chain = 'or';
			}
			else
			{
				$chain = 'and';
			}
			
			if (substr($ifunc, 0, strlen($chain)) == $chain)
			{
				$ifunc = substr($ifunc, strlen($chain));
			}
			
			if ($ifunc == 'where')
			{
				if (count($args) == 1 && is_callable($args[0]))
				{
					return $this->_where($args[0], $chain);
				}
				
				if (count($args) != 3)
				{
					throw new \Exception('Insufficient arguments for WHERE clause');
				}
				
				list($property, $operator, $value) = $args;
				
				
				if (is_string($operator))
				{
					switch($operator) {
					case '=':
					case '==':
						$operator = self::WHERE_EQ;
						break;
					case '<':
						$operator = self::WHERE_LT;
						break;
					case '<=':
						$operator = self::WHERE_LTEQ;
						break;
					case '>':
						$operator = self::WHERE_GT;
						break;
					case '>=':
						$operator = self::WHERE_GTEQ;
						break;
					}
				}
			}
			else
			{
				if (count($args) != 2)
				{
					throw new \Exception('Insufficient arguments for WHERE clause');
				}
				
				$opstr = substr($ifunc, 5);
				
				switch(strtolower($opstr)) {
				case 'eq':
				case 'equals':
					$operator = self::WHERE_EQ;
					break;
				case 'lt':
				case 'lessthan':
					$operator = self::WHERE_LT;
					break;
				case 'lteq':
				case 'lessthanequal':
				case 'lessthanequals':
				case 'lessthanorequal':
				case 'lessthanorequals':
					$operator = self::WHERE_LTEQ;
					break;
				case 'gt':
				case 'greaterthan':
					$operator = self::WHERE_GT;
					break;
				case 'gteq':
				case 'greaterthanequal':
				case 'greaterthanequals':
				case 'greaterthanorequal':
				case 'greaterhanorequals':
					$operator = self::WHERE_GTEQ;
					break;
				default:
					throw new Exception('Unknown Operator');
				}
				
				list($property, $value) = $args;
			}
			
			$this->_where($property, $chain, $operator, $value);
			return $this;
		}
		
		if (count($this->__results) > 0)
		{
			return call_user_func_array(
				[$this->__results[$this->__resIndex], $func],
				$args
			);
		}
		else if (isset($this->__changed[-1]))
		{
			return call_user_func_array(
				[$this->__changed[-1], $func],
				$args
			);
		}
		
		print "<pre>"; debug_print_backtrace();
		throw new \Exception("No such method: {$func}");
	}
	
	public static function __callStatic($func, $args)
	{
		if (self::_isWhere($func))
		{
			$_class = get_called_class();
			$instance = new $_class;
			
			return call_user_func_array([$instance, $func], $args);
		}
		
		throw new \Exception("No such static method");
	}
}
