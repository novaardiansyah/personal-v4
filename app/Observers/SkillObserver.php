<?php

namespace App\Observers;

use App\Models\Skill;

class SkillObserver
{
  /**
   * Handle the Skill "created" event.
   */
  public function created(Skill $skill): void
  {
    $this->_log('Created', $skill);
  }

  /**
   * Handle the Skill "updated" event.
   */
  public function updated(Skill $skill): void
  {
    $this->_log('Updated', $skill);
  }

  /**
   * Handle the Skill "deleted" event.
   */
  public function deleted(Skill $skill): void
  {
    $this->_log('Deleted', $skill);
  }

  /**
   * Handle the Skill "restored" event.
   */
  public function restored(Skill $skill): void
  {
    $this->_log('Restored', $skill);
  }

  /**
   * Handle the Skill "force deleted" event.
   */
  public function forceDeleted(Skill $skill): void
  {
    $this->_log('Force Deleted', $skill);
  }

  private function _log(string $event, Skill $skill): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Skill',
      'subject_type' => Skill::class,
      'subject_id'   => $skill->id,
    ], $skill);
  }
}