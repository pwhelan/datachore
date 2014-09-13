<?php

namespace Datachore;

class Collection extends \Illuminate\Support\Collection
{
	public function save()
	{
		$chore = new Datachore;
		$chore->save($this);
	}
}
