<?php

namespace model;

use \Datachore\Type;

class Testcase extends \Datachore\Model
{
	protected $properties = [
		'tests'		=> Type::Set
	];
	
	protected function define()
	{
		$this->properties['tests']->type(Type::Key);
	}
}
