<?php

namespace model;

use \Datachore\Type;

class Test extends \Datachore\Model
{
	protected $properties = [
		'name'		=> Type::String,
		'ref'		=> Type::Key,
		'counter'	=> Type::Integer,
		'datetime'	=> Type::Timestamp,
		'price'		=> Type::Double,
		'is_deleted'	=> Type::Boolean,
		'description'	=> Type::Blob
	];
}
