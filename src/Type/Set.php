<?php

namespace Datachore\Type;

class Set implements \Datachore\TypeInterface
{
	private $type = null;
	
	public function type($type = null)
	{
		if ($this->type == null && $type)
		{
			if ($type instanceof \Datachore\TypeInterface)
			{
				$this->type = $type;
			}
			else
			{
				$this->type = \Datachore\Type::getTypeFromEnum($type);
			}
		}
		return $this->type;
	}
}
