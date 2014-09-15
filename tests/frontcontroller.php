<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/model/Test.php';
require_once __DIR__.'/model/Reference.php';

use model\Test;
use model\Reference;


$app = new Slim\Slim;
$datastore = new Datachore\Datastore\GoogleRemoteApi;


if (extension_loaded('xdebug'))
{
	xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
}

register_shutdown_function(function() {
	
	
	$data = xdebug_get_code_coverage();
	xdebug_stop_code_coverage();
	
	
	$file = $_SERVER['SCRIPT_NAME'];
	file_put_contents(
		__DIR__ ."/coverage/". $file . '.' . time() . mt_rand(100, 10000000),
		serialize($data)
	);
	
});


$app->get('/coverage', function() {
$app->get('/remoteapisettings', function() {
	print json_encode([
		'application_id' => $_SERVER['APPLICATION_ID'],
		'remote_api' => [
			'host'	=> $_SERVER['REMOTE_API_HOST'],
			'port'	=> $_SERVER['REMOTE_API_PORT'],
			'id'	=> str_random(10) // 'IKLVcmdEFw' // 10 letter random hash
		]
	]);
});

	
	$data = xdebug_get_code_coverage();
	xdebug_stop_code_coverage();
	
	$files  = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			__DIR__.'/coverage',
			FilesystemIterator::SKIP_DOTS
		),
		RecursiveIteratorIterator::SELF_FIRST
	);
	
	$coverage = [];
	
	foreach ($files as $file) {
		
		$data = unserialize(file_get_contents($file));
		unlink($file);
		unset($file);
		$filter = new PHP_CodeCoverage_Filter();
		
		foreach ($data as $file => $lines)
		{
			if (!isset($coverage[$file]))
			{
				$coverage[$file] = $lines;
				/*
				[
					'md5' => md5_file($file),
					'coverage' => $lines
				];
				*/
			}
			else
			{
				foreach ($lines as $line => $flag)
				{
					/*
					if (
						!isset($coverage[$file]['coverage'][$line]) ||
						$flag > $coverage[$file]['coverage'][$line]
					)
					{
						$coverage[$file]['coverage'][$line] = $flag;
					}
					*/
					if (
						!isset($coverage[$file][$line]) ||
						$flag > $coverage[$file][$line]
					)
					{
						$coverage[$file][$line] = $flag;
					}
				}
			}
		}
	}
	
	print serialize($coverage);
});


$app->post('/test/collection', function() use ($app) {
	
	$tests = new Datachore\Collection;
	
	
	foreach ($app->request->post('names') as $name)
	{
		$test = new Test;
		$test->name = $name;
		
		$tests[] = $test;
	}
	
	
	$tests->save();
	print json_encode($tests->map(
		function($test) {
			return $test->toArray();
		}
	)->toArray());
	
});

$app->post('/test/types', function() use ($app) {
	
	$test = new Test;
	
	foreach ($app->request->post() as $key => $val)
	{
		$test->{$key} = $val;
	}
	
	$test->save();
	
	print json_encode($test->toArray());
});

$app->get('/info', function() {
	phpinfo();
	print "<pre>"; print_r($_SERVER);
});

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

$app->post('/query/(:kind)', function($kind) use ($app) {
	
	$operators = [
		'equals'		=> '==',
		'lessthan'		=> '<',
		'lessthanequals'	=> '<=',
		'greaterthan'		=> '>',
		'greaterthanequals'	=> '>='
	];
	
	
	$where = $app->request->post('where');
	if (empty($where))
	{
		$query = call_user_func(['model\\'.$kind, 'all']);
	}
	else
	{
		$query = call_user_func(['model\\'.$kind, 'where'], function($q) use ($where, $operators) {
			foreach ($where as $cond)
			{
				$op = in_array($cond['op'], array_keys($operators)) ?
					$operators[$cond['op']] : $op;
				
				$q->andWhere($cond['col'], $op, $cond['value']);
			}
			
		})->get();
	}
	
	
	print json_encode($query->map(function($result) {
		return $result->toArray();
	})->toArray(), JSON_PRETTY_PRINT);
});

$app->get('/test/(:id)', function($id) use ($app) {
	
	$test = Test::find($id);
	print json_encode($test->toArray());
});

$app->run();
