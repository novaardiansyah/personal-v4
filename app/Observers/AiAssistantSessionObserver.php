<?php

namespace App\Observers;

use App\Models\AiAssistantSession;
use Illuminate\Support\Str;

class AiAssistantSessionObserver
{
  public function creating(AiAssistantSession $session): void
  {
    if (!$session->uuid) {
      $session->uuid = uuid7();
    }

    if (!$session->user_id) {
      $session->user_id = getUser()?->id;
    }
  }

  public function created(AiAssistantSession $session): void
  {
    $this->_log('Created', $session);
  }

  public function updated(AiAssistantSession $session): void
  {
    $this->_log('Updated', $session);
  }

  public function deleted(AiAssistantSession $session): void
  {
    $this->_log('Deleted', $session);
  }

  public function restored(AiAssistantSession $session): void
  {
    $this->_log('Restored', $session);
  }

  public function forceDeleted(AiAssistantSession $session): void
  {
    $this->_log('Force Deleted', $session);
  }

  private function _log(string $event, AiAssistantSession $session): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'AiAssistantSession',
      'subject_type' => AiAssistantSession::class,
      'subject_id'   => $session->id,
    ], $session->user);
  }
}
