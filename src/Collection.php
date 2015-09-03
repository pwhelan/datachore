<?php

namespace Datachore;

class Collection extends \Illuminate\Support\Collection
{
	private function __doOp($op)
	{
		if (count($this->items) > 0)
		{
			$transaction = $this->items[0]->startSave();
			
			foreach($this->items as $item)
			{
				$item->$op($transaction);
			}
			
			$this->items[0]->endSave($transaction, $this);
		}
	}
	
	public function save()
	{
		$this->__doOp('save');
	}
	
	public function delete()
	{
		$this->__doOp('delete');
	}
}
