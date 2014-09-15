<?php

namespace Datachore;

class Collection extends \Illuminate\Support\Collection
{
	public function save()
	{
		if (count($this->items) > 0)
		{
			$transaction = $this->items[0]->startSave();
			
			foreach($this->items as $item)
			{
				$item->save($transaction);
			}
			
			$this->items[0]->endSave($transaction, $this);
		}
	}
}
