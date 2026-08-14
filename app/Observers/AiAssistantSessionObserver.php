<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AiAssistantSession;

class AiAssistantSessionObserver
{
  public function saving(AiAssistantSession $session): void
  {
    if (empty($session->uuid)) {
      $session->uuid = uuid7();
    }

    if (empty($session->user_id)) {
      $session->user_id = getUser()?->id;
    }
  }

  public function creating(AiAssistantSession $session): void
  {
    if (empty($session->uuid)) {
      $session->uuid = uuid7();
    }

    if (empty($session->user_id)) {
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
      'model'        => 'AI Assistant Session',
      'subject_type' => AiAssistantSession::class,
      'subject_id'   => $session->id,
    ], $session);
  }
}
