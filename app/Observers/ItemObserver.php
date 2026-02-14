<?php

namespace App\Observers;

use App\Models\Item;

class ItemObserver
{
	public function creating(Item $item): void
	{
		$item->code = getCode('item');
	}

  public function created(Item $item): void
  {
    $this->_log('Created', $item);
  }

  public function updated(Item $item): void
  {
    $this->_log('Updated', $item);
  }

  public function deleted(Item $item): void
  {
    $this->_log('Deleted', $item);
  }

  public function restored(Item $item): void
  {
    $this->_log('Restored', $item);
  }

  public function forceDeleted(Item $item): void
  {
    $this->_log('Force Deleted', $item);
  }

  private function _log(string $event, Item $item): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Item',
      'subject_type' => Item::class,
      'subject_id'   => $item->id,
    ], $item);
  }
}
