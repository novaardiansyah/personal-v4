<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DiscordWebhook;

class DiscordWebhookObserver
{
  public function creating(DiscordWebhook $discordWebhook): void
  {
    if (empty($discordWebhook->uid)) {
      $discordWebhook->uid = uuid7();
    }
  }

  public function updating(DiscordWebhook $discordWebhook): void
  {
    if (empty($discordWebhook->uid)) {
      $discordWebhook->uid = uuid7();
    }
  }

  public function created(DiscordWebhook $discordWebhook): void
  {
    $this->_log('Created', $discordWebhook);
  }

  public function updated(DiscordWebhook $discordWebhook): void
  {
    $this->_log('Updated', $discordWebhook);
  }

  public function deleted(DiscordWebhook $discordWebhook): void
  {
    $this->_log('Deleted', $discordWebhook);
  }

  public function restored(DiscordWebhook $discordWebhook): void
  {
    $this->_log('Restored', $discordWebhook);
  }

  public function forceDeleted(DiscordWebhook $discordWebhook): void
  {
    $this->_log('Force Deleted', $discordWebhook);
  }

  private function _log(string $event, DiscordWebhook $discordWebhook): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Discord Webhook',
      'subject_type' => DiscordWebhook::class,
      'subject_id'   => $discordWebhook->id,
    ], $discordWebhook);
  }
}
