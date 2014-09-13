<?php

namespace Datachore\Datastore;

/** Implements the Data store by the Remote Stub API used by GAE SDK.
 */
class GoogleRemoteApi extends \Datachore\Datastore
{
	protected function __initialize(array $config = null)
	{
		
	}
	
	public function call()
	{
		$args = func_get_args();
		$call = $args[0];
		
		return call_user_func_array([$this, 'call'], $args);
	}
	
	private function _callMethod($methodName, $request, $response)
	{
		\google\appengine\runtime\ApiProxy::makeSyncCall(
			'datastore_v4',
			ucfirst($methodName),
			$request,
			$response
		);
		
		return $response;
	}
	
	public function __call($func, $args)
	{
		$responseClass = str_replace('Request', 'Response', get_class($args[1]));
		$response = new $responseClass;
		
		return $this->_callMethod($func, $args[1], $response);
	}
	
	private function _getBaseUrl()
	{
		return '/datastore/v1beta1/datasets';
	}
	
	private function _getFullBaseUrl()
	{
		return rtrim($this->_host, '/') . '/' . ltrim($this->_getBaseUrl(), '/');
	}
	
	private function _getUrlForMethod($methodName)
	{
		return $this->_getFullBaseUrl() . '/' . $this->_dataset . '/' . $methodName;
	}
	
	public function Factory($type)
	{
		$className = 'google\\appengine\\datastore\\v4\\'.$type;
		return new $className;
	}
	
	public function isInstanceOf($object, $typeName)
	{
		return get_class($objeect) == 'google\\appengine\\datastore\\v4\\'.$typeName;
	}
}
