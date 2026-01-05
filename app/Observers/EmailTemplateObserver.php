<?php

namespace App\Observers;

use App\Models\EmailTemplate;

class EmailTemplateObserver
{
  public function creating(EmailTemplate $emailTemplate): void
  {
    $emailTemplate->code = getCode('email_template');
  }

  public function created(EmailTemplate $emailTemplate): void
  {
    $this->_log('Created', $emailTemplate);
  }

  public function updated(EmailTemplate $emailTemplate): void
  {
    $this->_log('Updated', $emailTemplate);
  }

  public function deleted(EmailTemplate $emailTemplate): void
  {
    $this->_log('Deleted', $emailTemplate);
  }

  public function restored(EmailTemplate $emailTemplate): void
  {
    $this->_log('Restored', $emailTemplate);
  }

  public function forceDeleted(EmailTemplate $emailTemplate): void
  {
    $this->_log('Force Deleted', $emailTemplate);
  }

  private function _log(string $event, EmailTemplate $emailTemplate): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Email Template',
      'subject_type' => EmailTemplate::class,
      'subject_id'   => $emailTemplate->id,
    ], $emailTemplate);
  }
}
