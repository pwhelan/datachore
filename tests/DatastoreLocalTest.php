<?php

require_once __DIR__.'/model/Test.php';
require_once __DIR__.'/model/Reference.php';

define('APPENGINE_BASE_SDK', __DIR__.'/../google_appengine/php/sdk/');


class DatastoreLocalTest extends PHPUnit_Framework_TestCase
{
	private static $counter = 1;
	
	
	public static function setUpBeforeClass()
	{
		chdir(APPENGINE_BASE_SDK);
		require_once 'google/appengine/runtime/autoloader.php';
		
		
		for ($i = 0; $i < 128; $i++)
		{
			$fp = @fsockopen("127.0.0.1", 8080, $errno, $errstr);
			if (!$fp)
			{
				sleep(1);
			}
			else
			{
				fclose($fp);
				break;
			}
		}
		
		date_default_timezone_set('GMT');
		
		$settings = json_decode(
			GuzzleHttp\get("http://127.0.0.1:8080/remoteapisettings")
				->getBody()
		);
		
		require_once APPENGINE_BASE_SDK.'google/appengine/runtime/ApiProxy.php';
		require_once APPENGINE_BASE_SDK.'google/appengine/runtime/RemoteApiProxy.php';
		\google\appengine\runtime\ApiProxy::setApiProxy(
			new \google\appengine\runtime\RemoteApiProxy(
				$settings->remote_api->host,
				$settings->remote_api->port,
				$settings->remote_api->id
			)
		);
		
		$datastore = new Datachore\Datastore\GoogleRemoteApi([
			'datasetId'	=> $settings->application_id
		]);
	}
	
	public static function tearDownAfterClass()
	{
		chdir(__DIR__.'/../');
	}
	
	public function testInsert()
	{
		$now = time();
		
		$test = new model\Test;
		
		$test->name = "Foobar";
		$test->counter = self::$counter++;
		$test->price = 13.37;
		$test->description = "Friendly little bugger";
		$test->datetime = $now;
		$test->is_deleted = false;
		
		$test->save();
		
		
		sleep(1);
		$test = model\Test::find($test->id);
		
		$this->assertStringMatchesFormat('%d', $test->id);
		
		$this->assertEquals('Foobar', $test->name);
		$this->assertEquals(self::$counter-1, $test->counter);
		$this->assertEquals(13.37, $test->price);
		$this->assertEquals("Friendly little bugger", $test->description);
		$this->assertEquals($now, $test->datetime);
	}
	
	public function testToArray()
	{
		$now = new DateTime("1983-03-30");
		
		$test = new model\Test;
		
		$test->name = "Foobar";
		$test->counter = self::$counter++;
		$test->price = 13.37;
		$test->description = "Friendly little bugger";
		$test->datetime = $now;
		$test->is_deleted = false;
		
		
		$array = $test->toArray();
		
		$this->assertEquals('Foobar', $array['name']);
		$this->assertEquals(self::$counter-1, $array['counter']);
		$this->assertEquals(13.37, $array['price']);
		$this->assertEquals("Friendly little bugger", $array['description']);
		$this->assertEquals($now->getTimestamp(), $array['datetime']);
		
		
		$test->datetime = $test->datetime->getTimestamp();
		$array = $test->toArray();
		
		$this->assertEquals($now->getTimestamp(), $test->datetime);
	}
	
	public function testInsertWithReference()
	{
		$now = time();
		
		$ref = new model\Reference;
		$ref->name = "A friend indeed";
		$ref->save();
		
		$test = new model\Test;
		$test->name = "Barfoo";
		$test->counter = self::$counter++;
		$test->price = 4.20;
		$test->description = "Not as Amicable";
		$test->datetime = $now;
		$test->is_deleted = true;
		$test->ref = $ref;
		$test->save();
		
		sleep(1);
		
		$test = model\Test::find($test->id);
		$this->assertEquals($ref->id, $test->ref->id);
	}
	
	public function testUpdateWithReference()
	{
		$test = model\Test::where('counter', '<=', self::$counter)
			->first();
		
		$ref = new model\Reference;
		$ref->name = "Some leech called Todd";
		$ref->save();
		
		$old = $test->toArray();
		$test->ref = $ref;
		$test->save();
		
		$this->assertEquals($ref->id, $test->ref->id);
		foreach ($old as $k => $v)
		{
			if ($k == 'ref')
			{
				continue;
			}
			$this->assertEquals($v, $test->{$k}, "Old value for {$k} does not match new value");
		}
	}
	
	public function testInsertCollection()
	{
		$set = self::$counter++;
		$names = [
			"FootMouth",
			"Batman",
			"Robin",
			"Gonzo",
			"Lucy"
		];
		
		
		$tests = new Datachore\Collection;
		
		foreach ($names as $name)
		{
			$test = new model\Test;
			$test->name = $name;
			$test->counter = $set;
			$tests[] = $test;
		}
		
		$tests->save();
		
		foreach ($tests as $idx => $test)
		{
			$this->assertStringMatchesFormat('%d', $test->id);
			$this->assertEquals($set, $test->counter);
			$this->assertEquals($names[$idx], $test->name);
		}
	}
	
	public function testInsertDateTime()
	{
		$date = new DateTime("1983-03-30");
		
		
		$test = new model\Test;
		$test->name = __METHOD__;
		$test->datetime = $date;
		$test->save();
		
		$this->assertEquals($test->datetime->getTimestamp(), $date->getTimestamp());
		
		// Datastore takes a while to commit, can we block on it?
		sleep(1);
		
		$test = model\Test::find($test->id);
		$this->assertEquals($test->datetime, $date->getTimestamp());
	}
	
	public function testInsertDateTimeString()
	{
		$date = "1983-03-30";
		
		
		$test = new model\Test;
		$test->name = __METHOD__;
		$test->datetime = $date;
		$test->save();
		
		$this->assertEquals($test->datetime, $date);
		
		sleep(1);
		
		$test = model\Test::find($test->id);
		$this->assertEquals($test->datetime, strtotime($date));
	}
	
	public function testLessThanEquals()
	{
		$half = (int)(self::$counter / 2);
		
		
		$tests = model\Test::where('counter', '<=', $half)->get();
		foreach ($tests as $test)
		{
			$this->assertLessThanOrEqual($half, $test->counter);
		}
	}
	
	public function testGreaterThanEquals()
	{
		$half = (int)(self::$counter / 2);
		
		
		$tests = model\Test::where('counter', '>=', $half)->get();
		foreach ($tests as $test)
		{
			$this->assertGreaterThanOrEqual($half, $test->counter);
		}
	}
	
	public function testLessThan()
	{
		$half = (int)(self::$counter / 2);
		
		
		$tests = model\Test::where('counter', '<', $half)->get();
		foreach ($tests as $test)
		{
			$this->assertLessThan($half, $test->counter);
		}
	}
	
	public function testGreaterThan()
	{
		$half = (int)(self::$counter / 2);
		
		
		$tests = model\Test::where('counter', '>', $half)->get();
		foreach ($tests as $test)
		{
			$this->assertGreaterThan($half, $test->counter);
		}
	}
	
	public function testQueryByString()
	{
		$tests = model\Test::where('name', '==', 'Batman')->get();
		foreach($tests as $test)
		{
			$this->assertEquals('Batman', $test->name);
		}
	}
	
	public function testQueryByDateTime()
	{
		$tests = model\Test::where('datetime', '==', "1983-03-30")->get();
		foreach($tests as $test)
		{
			$this->assertEquals(strtotime("1983-03-30"), $test->datetime);
		}
		
		$tests = model\Test::where('datetime', '==', new DateTime("1983-03-30"))->get();
		foreach($tests as $test)
		{
			$this->assertEquals(strtotime("1983-03-30"), $test->datetime);
		}
		
		$tests = model\Test::where('datetime', '==', strtotime("1983-03-30"))->get();
		foreach($tests as $test)
		{
			$this->assertEquals(strtotime("1983-03-30"), $test->datetime);
		}
	}
	
	public function testQueryByBoolean()
	{
		$tests = model\Test::where('is_deleted', '==', true)->get();
		foreach($tests as $test)
		{
			$this->assertEquals(true, $test->is_deleted);
		}
	}
	
	public function testQueryByDouble()
	{
		$tests = model\Test::where('price', '<', 13.37)->get();
		foreach($tests as $test)
		{
			$this->assertLessThan(13.37, $test->price);
		}
	}
	
	public function testQueryByBlob()
	{
		$tests = model\Test::where('description', '==', "Not as Amicable");
		foreach ($tests as $test)
		{
			$this->assertEquals("Not as Amicable", $test->description);
		}
	}
}
