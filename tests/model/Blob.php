<?php

namespace model;

use \Datachore\Type;

class Blob extends \Datachore\Model
{
	protected $properties = [
		'blob'		=> Type::Blob,
		'text'		=> Type::BlobKey
	];
}
