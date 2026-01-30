<?php

namespace App\Observers;

use App\Models\Note;

class NoteObserver
{
  public function creating(Note $note): void
  {
    $note->code = getCode('note');
  }

  /**
   * Handle the Note "created" event.
   */
  public function created(Note $note): void
  {
    $this->_log('Created', $note);
  }

  /**
   * Handle the Note "updated" event.
   */
  public function updated(Note $note): void
  {
    $this->_log('Updated', $note);
  }

  /**
   * Handle the Note "deleted" event.
   */
  public function deleted(Note $note): void
  {
    $this->_log('Deleted', $note);
  }

  /**
   * Handle the Note "restored" event.
   */
  public function restored(Note $note): void
  {
    $this->_log('Restored', $note);
  }

  /**
   * Handle the Note "force deleted" event.
   */
  public function forceDeleted(Note $note): void
  {
    $this->_log('Force Deleted', $note);
  }

  private function _log(string $event, Note $note): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Note',
      'subject_type' => Note::class,
      'subject_id'   => $note->id,
    ], $note);
  }
}
