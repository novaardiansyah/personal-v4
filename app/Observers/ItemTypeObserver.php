<?php

namespace App\Observers;

use App\Models\ItemType;

class ItemTypeObserver
{
  /**
   * Handle the ItemType "created" event.
   */
  public function created(ItemType $itemType): void
  {
    $this->_log('Created', $itemType);
  }

  /**
   * Handle the ItemType "updated" event.
   */
  public function updated(ItemType $itemType): void
  {
    $this->_log('Updated', $itemType);
  }

  /**
   * Handle the ItemType "deleted" event.
   */
  public function deleted(ItemType $itemType): void
  {
    $this->_log('Deleted', $itemType);
  }

  /**
   * Handle the ItemType "restored" event.
   */
  public function restored(ItemType $itemType): void
  {
    $this->_log('Restored', $itemType);
  }

  /**
   * Handle the ItemType "force deleted" event.
   */
  public function forceDeleted(ItemType $itemType): void
  {
    $this->_log('Force Deleted', $itemType);
  }

  private function _log(string $event, ItemType $itemType): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Item Type',
      'subject_type' => ItemType::class,
      'subject_id'   => $itemType->id,
    ], $itemType);
  }
}
