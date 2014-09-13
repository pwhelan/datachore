<?php

namespace Datachore;

class Collection extends \Illuminate\Support\Collection
{
	public function save()
	{
		if (count($this->items) > 0)
		{
			list ($commit, $mutation) = $this->items[0]->startSave();
			
			foreach($this->items as $item)
			{
				$item->save($mutation);
			}
			
			$this->items[0]->endSave($commit, $mutation, $this);
		}
	}
}
