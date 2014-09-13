<?php


/** @TODO:
 *    * Get NULL key references to save correctly.
 */
namespace Datachore;

class Datachore
{
	private $_datasetId = null;
	private $_datastore = null;
	
	protected $_runQuery;
	protected $_query;
	protected $_filter;
	
	
	private function _clear()
	{
		$this->_runQuery = $this->datastore()->Factory('RunQueryRequest');
		$this->_query = $this->_runQuery->mutableQuery();
	}
	
	public function __construct($model)
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
	
	private function _kind_from_class($object = null)
	{
		if ($object === null)
		{
			$object = $this;
		}
		
		$className = get_class($object);
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
	
	public function save($collection = null)
	{
		if ($collection === null)
		{
			$collection = new Collection([$this]);
		}
		else if (!$collection instanceof Collection)
		{
			$collection = new Collection([$collection]);
		}
		
		
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
		$pairs = [];
		
		
		foreach ($collection as $model)
		{
			if ($model->key)
			{
				$entity = $mutation->addUpdate();
				$this->_GoogleKeyValue($entity->mutableKey(), $model->id, $model);
			}
			else
			{
				$entity = $mutation->addInsertAutoId();
				$this->_GoogleKeyValue($entity->mutableKey(), null, $model);
				
				// For now just save auto insert pairs
				$pairs[] = [$model, $entity];
			}
			
			$model->mergeIntoEntity($entity);
		}
		
		
		$rc = $this->datastore()->commit($this->datasetId(), $commit);
		
		$insertIds = $mutation->getInsertAutoIdList();
		for ($i = 0; $i < count($insertIds); $i++)
		{
			foreach ($pairs as $pair)
			{
				if ($pair[1] == $insertIds[$i])
				{
					$pair[0]->key = $rc->getDeprecatedMutationResult()
						->getInsertAutoIdKey($i);
				}
			}
		}
		
		return $rc;
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
	
	
	final protected function _GoogleKeyValue(\google\appengine\datastore\v4\Key $key, $id = null, $model = null)
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
				else if (\google\appengine\datastore\v4\Value)
				{
					$id = $id->getKeyValue();
				}
				else if (\google\appengine\datastore\v4\Key)
				{
					$id = $id;
				}
				else
				{
					throw new \Exception("Datachore: Unexpected Key Type: ".get_class($id));
				}
				
				$path->setId($id->getPathElement(0)->getId());
			}
		}
		
		$path->setKind($this->_kind_from_class($model));
		
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
			$value->setStringValue($rawValue);
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
