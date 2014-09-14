<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/model/Test.php';
require_once __DIR__.'/model/Reference.php';

use model\Test;
use model\Reference;


$app = new Slim\Slim;
$datastore = new Datachore\Datastore\GoogleRemoteApi;

$app->get('/test/insert/(:name)/(:counter)', function($name, $counter) {
	
	$test = new Test;
	$test->name = $name;
	$test->counter = $counter;
	$test->save();
	
	print json_encode($test->toArray());
});

$app->get('/test/update/(:id)/(:name)', function($id, $name) {
	
	$test = Test::where('id', '==', $id)->first();
	$test->name = $name;
	$test->save();
	
	print json_encode($test->toArray());
});

$app->get('/test/reference/(:testname)/(:refname)', function($testname, $refname) {
	
	$ref = new Reference;
	$ref->name = $refname;
	$ref->save();
	
	$test = new Test;
	$test->ref = $ref;
	$test->name = $testname;
	$test->save();
	
	print json_encode([
		'test'	=> $test->toArray(),
		'ref'	=> $ref->toArray()
	]);
});

$app->get('/test/orderby', function() {
	
	$collection = new Datachore\Collection;
	
	$name = "hello_there_all_".mt_rand(1000, 100000);
	
	for ($i = 0; $i < 10; $i++)
	{
		$test = new Test;
		$test->counter = $i;
		$test->name = $name;
		
		$collection[] = $test;
	}
	
	$collection->save();
	$ordered = model\Test::where('name', '==', $name)
		->orderBy('counter', 'asc')
		->get();
	
});

$app->run();
