<?php

class DatastoreTest extends PHPUnit_Framework_TestCase
{
	public function testEnvironment()
	{
		for ($i = 0; $i < 128; $i++)
		{
			$fp = fsockopen("127.0.0.1", 8080, $errno, $errstr);
			if (!$fp)
			{
				print "Waiting for Socket...\n";
				sleep(1);
			}
			else
			{
				fclose($fp);
				break;
			}
		}
		
		$pid = file_get_contents(__DIR__.'/../appengine.pid');
		print "PID = {$pid}\n";
		print "LOG FILE = \n".file_get_contents(__DIR__.'/../out.log');
		print "ERR FILE = \n".file_get_contents(__DIR__.'/../err.log');
		if (!file_exists('/proc/'.trim($pid)))
		{
			print "NO APPENGINE\n";
		}
		passthru("ps auwx");
		passthru("netstat -na --tcp");
	}
	
	public function testInsert()
	{
		try {
			$response = Guzzlehttp\get('http://127.0.0.1:8080/test/insert/foo/1');
			$insert = json_decode($response->getBody());
			
			$this->assertEquals($insert->name, "foo");
			$this->assertEquals($insert->counter, 1);
		}
		catch (GuzzleHttp\Exception\ServerException $e)
		{
			throw new Exception("Server error: ".$e->getResponse()->getBody());
		}
		catch (Exception $e)
		{
			throw new Exception("ERROR: ".get_class($e));
		}
	}
	
	public function testUpdate()
	{
		try {
			$insert = json_decode(
				Guzzlehttp\get('http://127.0.0.1:8080/test/insert/foo/'.mt_rand(1000, 1000000))
					->getBody()
			);
			
			$update = json_decode(
				Guzzlehttp\get("http://127.0.0.1:8080/test/update/{$insert->id}/foobar")
					->getBody()
			);
			
			$this->assertEquals($insert->id, $update->id);
			$this->assertEquals($insert->counter, $update->counter);
			$this->assertEquals($update->name, 'foobar');
		}
		catch (GuzzleHttp\Exception\ServerException $e)
		{
			throw new Exception("Server error: ".$e->getResponse()->getBody());
		}
		catch (Exception $e)
		{
			throw new Exception("ERROR: ".get_class($e));
		}
	}
	
	public function testReferences()
	{
		try {
			$objects = json_decode(
				Guzzlehttp\get('http://127.0.0.1:8080/test/reference/foobar/barfoo')
					->getBody()
			);
			
			$this->assertEquals($objects->test->ref->id, $objects->ref->id);
			$this->assertEquals($objects->test->name, "foobar");
			$this->assertEquals($objects->ref->name, "barfoo");
		}
		catch (GuzzleHttp\Exception\ServerException $e)
		{
			throw new Exception("Server error: ".$e->getResponse()->getBody());
		}
		catch (Exception $e)
		{
			throw new Exception("ERROR: ".get_class($e));
		}
	}
	
	public function testUpdateReferences()
	{
		try {
			$objects = json_decode(
				Guzzlehttp\get('http://127.0.0.1:8080/test/reference/foobar/barfoo')
					->getBody()
			);
			
			$test = json_decode(
				Guzzlehttp\get("http://127.0.0.1:8080/test/update/{$objects->test->id}/supermeatboy")
					->getBody()
			);
			
			$this->assertEquals($objects->test->id, $test->id);
			$this->assertEquals($objects->test->ref->id, $test->ref->id);
			$this->assertEquals($test->name, "supermeatboy");
		}
		catch (GuzzleHttp\Exception\ServerException $e)
		{
			throw new Exception("Server error: ".$e->getResponse()->getBody(), 0, $e);
		}
		catch (Exception $e)
		{
			throw new Exception("ERROR: ".get_class($e), 0, $e);
		}
	}
}
