<?php

namespace Datachore;

class Type
{
	const Boolean	= 1;
	const Double	= 2;
	const Integer	= 3;
	const String	= 4;
	const Timestamp = 5;
	const BlobKey	= 6;
	const Key	= 7;
	const Blob	= 8;
	
	public static function getTypeFromEnum($const)
	{
		switch ($const) {
		case self::Boolean:
			return new Type\Boolean;
		case self::Double:
			return new Type\Double;
		case self::Integer:
			return new Type\Integer;
		case self::String:
			return new Type\String;
		case self::Timestamp:
			return new Type\Timestamp;
		case self::BlobKey:
			return new Type\BlobKey;
		case self::Key:
			return new Type\Key;
		case self::Blob:
			return new Type\Blob;
		}
	}
}
