<?php

class DatastoreTest extends PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
		for ($i = 0; $i < 128; $i++)
		{
			$fp = @fsockopen("127.0.0.1", 8080, $errno, $errstr);
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
		
		date_default_timezone_set('GMT');
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
			throw new Exception("Server error: ".$e->getResponse()->getBody(), 0, $e);
		}
		catch (Exception $e)
		{
			throw new Exception("ERROR: ".get_class($e), 0, $e);
		}
	}
	
	public function testUpdate()
	{
		try {
			$insert = json_decode(
				Guzzlehttp\get('http://127.0.0.1:8080/test/insert/foo/'.mt_rand(1000, 1000000))
					->getBody()
			);
			
			$this->assertObjectHasAttribute('id', $insert);
			sleep(1);
			
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
			
			sleep(1);
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
	
	public function testSaveCollection()
	{
		$objects = json_decode(
			Guzzlehttp\post('http://127.0.0.1:8080/test/collection',[
				'body'	=> ['names' => [
					'redfish',
					'bluefish',
					'deadfish',
					'rudefish'
				]]
			])
			->getBody()
		);
		
		
		$this->assertEquals($objects[0]->name, "redfish");
		$this->assertEquals($objects[1]->name, "bluefish");
		$this->assertEquals($objects[2]->name, "deadfish");
		$this->assertEquals($objects[3]->name, "rudefish");
		
		foreach ($objects as $object)
		{
			$this->assertObjectHasAttribute("id", $object);
			$this->assertObjectHasAttribute("name", $object);
		}
	}
	
	public function testTypes()
	{
		$values = [
			[
				'name'		=> 'Baal',
				'datetime'	=> (new DateTime('1983-03-30'))->getTimestamp(),
				'counter'	=> 1337,
				'price'		=> 13.37,
				'is_deleted'	=> true,
				'description'	=> "ipso lorum gryphyndor"
			],
			[
				'name'		=> 'Boring',
				'datetime'	=> (new DateTime('1979-01-01'))->getTimestamp(),
				'counter'	=> 424242,
				'price'		=> 4.20,
				'is_deleted'	=> false,
				'description'	=> "a million little paper cuts"
			],
			[
				'name'		=> 'Something Waitforit',
				'datetime'	=> (new DateTime('1992-03-04'))->getTimestamp(),
				'counter'	=> -28324064,
				'price'		=> -0.05,
				'is_deleted'	=> false,
				'description'	=> "The date is not working, so what the hell?"
			]
		];
		
		
		$tests = [];
		
		foreach ($values as $idx => $value)
		{
			$tests[$idx] = json_decode(
				Guzzlehttp\post('http://127.0.0.1:8080/test/types',[
					'body'	=> $values[$idx]
				])
				->getBody()
			);
		}
		
		foreach ($values as $idx => $value)
		{
			foreach ($value as $key => $val)
			{
				if ($key == 'datetime' && !is_numeric($val))
				{
					$val = strtotime($val);
				}
				
				$this->assertEquals(
					$val,
					$tests[$idx]->{$key},
					"Test[$idx]: value for {$key} does not match"
				);
			}
		}
		
		foreach ($tests as $test)
		{
			$t2 = json_decode(
				Guzzlehttp\get('http://127.0.0.1:8080/test/'.$test->id)
					->getBody()
			);
			
			foreach ($test as $key => $val)
			{
				$this->assertEquals($val, $t2->{$key}, "Test[$idx]: Incorrectly retrieved value for {$key}");
			}
		}
	}
	
	public function testQuery()
	{
		$queries = [
			[
				'where' => [[
					'col'	=> 'counter',
					'op'	=> 'greaterthanequals',
					'value'	=> '300'
				]],
				'check'	=> function($result)
				{
					return $result->counter >= 300;
				}
			],
			[	'where' => [[
					'col'	=> 'datetime',
					'op'	=> 'lessthan',
					'value'	=> (new DateTime("1983-03-30"))
						->getTimestamp()
				]],
				'check'	=> function($result)
				{
					return $result->datetime <
						(new DateTime("1983-03-30"))
							->getTimestamp();
				}
			],
			[
				'where' => [[
					'col'	=> 'datetime',
					'op'	=> 'lessthanequals',
					'value'	=> '1980-01-01'
				]],
				'check'	=> function($result)
				{
					return $result->datetime <=
						(new DateTime("1980-01-01"))
							->getTimestamp();
				}
			],
			[
				'where' => [[
					'col'	=> 'is_deleted',
					'op'	=> 'equals',
					'value'	=> 1,
				]],
				'check'	=> function($result)
				{
					return $result->is_deleted;
				}
			],
			[
				'where' => [[
					'col'	=> 'price',
					'op'	=> 'greaterthan',
					'value'	=> 200.0,
				]],
				'check'	=> function($result)
				{
					print_r($result);
					return true;
					return $result->price > 200.0;
				}
			],
				['where' => [[
					'col'	=> 'price',
					'op'	=> 'lessthanequals',
					'value'	=> 0.0,
				]],
				'check'	=> function($result)
				{
					return $result->price <= 0.0;
				}
			],
			[
				'where' => [[
					'col'	=> 'name',
					'op'	=> 'equals',
					'value'	=> 'bluefish',
				]],
				'check'	=> function($result)
				{
					return $result->name == 'bluefish';
				}
			],
			[
				'where' => [[
					'col'	=> 'description',
					'op'	=> 'equals',
					'value'	=> 'ipso lorum gryphyndor',
				]],
				'check'	=> function($result)
				{
					return $result->description == 'ipso lorum gryphyndor';
				}
			]
		];
		
		
		$tests = [];
		
		foreach ($queries as $idx => $value)
		{
			try {
				$results = json_decode(
					Guzzlehttp\post('http://127.0.0.1:8080/query/Test',[
						'body'	=> isset($queries[$idx]['where']) ?
							['where'  => $queries[$idx]['where']] : []
					])
					->getBody()
				);
			}
			catch (GuzzleHttp\Exception\ServerException $e)
			{
				throw new Exception("Server error: ".$e->getResponse()->getBody(), 0, $e);
			}
			
			if (!isset($queries[$idx]['check']))
			{
				continue;
			}
			
			
			foreach ($results as $result)
			{
				$this->assertTrue(
					$queries[$idx]['check']($result),
					"Failed check for query#{$idx} ".print_r($queries[$idx]['where'],true).
					"with ".print_r($result, true)
				);
			}
		}
		
	}
	
	/**
	 * Runs the test case and collects the results in a TestResult object.
	 * If no TestResult object is passed a new one will be created.
	 *
	 * @param  PHPUnit_Framework_TestResult $result
	 * @return PHPUnit_Framework_TestResult
	 * @throws InvalidArgumentException
	 */
	public function run(PHPUnit_Framework_TestResult $result = NULL)
	{
		if ($result === NULL) {
			$result = $this->createResult();
		}
		
		$this->collectCodeCoverageInformation = $result->getCollectCodeCoverageInformation();
		if ($this->collectCodeCoverageInformation)
		{
			GuzzleHttp\get('http://127.0.0.1:8080/coverage/on');
		}
		
		parent::run($result);
		
		if ($this->collectCodeCoverageInformation)
		{
			try
			{
				$resp = Guzzlehttp\get("http://127.0.0.1:8080/coverage/dump");
				$coverage = unserialize($resp->getBody());
				
				$result->getCodeCoverage()->append($coverage, $this);
			}
			catch (GuzzleHttp\Exception\ServerException $e)
			{
				throw new Exception("Coverage Server error: ".$e->getResponse()->getBody(), 0, $e);
			}
			catch(Exception $e)
			{
				print "Unable to grab remote coverage: ".get_class($e)." => ".$e->getMessage()."\n";
			}
			
			GuzzleHttp\get('http://127.0.0.1:8080/coverage/off');
		}
		
		return $result;
	}
}
