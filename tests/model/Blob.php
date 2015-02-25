<?php

namespace model;

use \Datachore\Type;

class Blob extends \Datachore\Model
{
	protected $properties = [
		'random'	=> Type::Integer,
		'blob'		=> Type::Blob,
		'text'		=> Type::BlobKey
	];
}
