<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AiAssistantMessage;

class AiAssistantMessageObserver
{
  public function saving(AiAssistantMessage $message): void
  {
    if (empty($message->uuid)) {
      $message->uuid = uuid7();
    }

    if (empty($message->user_id)) {
      $message->user_id = getUser()?->id;
    }
  }

  public function creating(AiAssistantMessage $message): void
  {
    if (empty($message->uuid)) {
      $message->uuid = uuid7();
    }

    if (empty($message->user_id)) {
      $message->user_id = getUser()?->id;
    }
  }

  public function created(AiAssistantMessage $message): void
  {
    $this->_log('Created', $message);
  }

  public function updated(AiAssistantMessage $message): void
  {
    $this->_log('Updated', $message);
  }

  public function deleted(AiAssistantMessage $message): void
  {
    $this->_log('Deleted', $message);
  }

  public function restored(AiAssistantMessage $message): void
  {
    $this->_log('Restored', $message);
  }

  public function forceDeleted(AiAssistantMessage $message): void
  {
    $this->_log('Force Deleted', $message);
  }

  private function _log(string $event, AiAssistantMessage $message): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'AI Assistant Message',
      'subject_type' => AiAssistantMessage::class,
      'subject_id'   => $message->id,
    ], $message);
  }
}
