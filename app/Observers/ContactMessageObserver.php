<?php

namespace App\Observers;

use App\Models\ContactMessage;

class ContactMessageObserver
{
  /**
   * Handle the ContactMessage "created" event.
   */
  public function created(ContactMessage $contactMessage): void
  {
    $this->_log('Created', $contactMessage);
  }

  /**
   * Handle the ContactMessage "updated" event.
   */
  public function updated(ContactMessage $contactMessage): void
  {
    $this->_log('Updated', $contactMessage);
  }

  /**
   * Handle the ContactMessage "deleted" event.
   */
  public function deleted(ContactMessage $contactMessage): void
  {
    $this->_log('Deleted', $contactMessage);
  }

  /**
   * Handle the ContactMessage "restored" event.
   */
  public function restored(ContactMessage $contactMessage): void
  {
    $this->_log('Restored', $contactMessage);
  }

  /**
   * Handle the ContactMessage "force deleted" event.
   */
  public function forceDeleted(ContactMessage $contactMessage): void
  {
    $this->_log('Force Deleted', $contactMessage);
  }

  private function _log(string $event, ContactMessage $contactMessage): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Contact Message',
      'subject_type' => ContactMessage::class,
      'subject_id'   => $contactMessage->id,
    ], $contactMessage);
  }
}
