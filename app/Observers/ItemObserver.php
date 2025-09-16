<?php

namespace App\Observers;

use App\Models\Item;

class ItemObserver
{
  /**
   * Handle the Item "created" event.
   */
  public function created(Item $item): void
  {
    $this->_log('Created', $item);
  }

  /**
   * Handle the Item "updated" event.
   */
  public function updated(Item $item): void
  {
    $this->_log('Updated', $item);
  }

  /**
   * Handle the Item "deleted" event.
   */
  public function deleted(Item $item): void
  {
    $this->_log('Deleted', $item);
  }

  /**
   * Handle the Item "restored" event.
   */
  public function restored(Item $item): void
  {
    $this->_log('Restored', $item);
  }

  /**
   * Handle the Item "force deleted" event.
   */
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
