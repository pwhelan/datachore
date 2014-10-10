<?php

namespace model;

use \Datachore\Type;

class Testcase extends \Datachore\Model
{
	protected $properties = [
		'tests'		=> Type::Set,
		'results'	=> Type::Set
	];
	
	protected function define()
	{
		$this->properties['tests']->type(Type::Key);
		$this->properties['results']->type(new Type\Integer);
	}
}
