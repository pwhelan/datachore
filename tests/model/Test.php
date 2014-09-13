<?php

namespace model;

use \Datachore\Type;

class Test extends \Datachore\Model
{
	protected $properties = [
		'name'		=> Type::String,
		'ref'		=> Type::Key,
		'datetime'	=> Type::Timestamp,
		'counter'	=> Type::Integer
	];
}
