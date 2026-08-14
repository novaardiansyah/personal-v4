<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AiAssistantContext;

class AiAssistantContextObserver
{
  public function saving(AiAssistantContext $context): void
  {
    if (empty($context->uuid)) {
      $context->uuid = uuid7();
    }
  }

  public function creating(AiAssistantContext $context): void
  {
    if (empty($context->uuid)) {
      $context->uuid = uuid7();
    }
  }

  public function created(AiAssistantContext $context): void
  {
    $this->_log('Created', $context);
  }

  public function updated(AiAssistantContext $context): void
  {
    $this->_log('Updated', $context);
  }

  public function deleted(AiAssistantContext $context): void
  {
    $this->_log('Deleted', $context);
  }

  public function restored(AiAssistantContext $context): void
  {
    $this->_log('Restored', $context);
  }

  public function forceDeleted(AiAssistantContext $context): void
  {
    $this->_log('Force Deleted', $context);
  }

  private function _log(string $event, AiAssistantContext $context): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'AI Assistant Context',
      'subject_type' => AiAssistantContext::class,
      'subject_id'   => $context->id,
    ], $context);
  }
}
