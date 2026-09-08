<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DiscordMessage;

class DiscordMessageObserver
{
  public function creating(DiscordMessage $discordMessage): void
  {
    if (empty($discordMessage->uid)) {
      $discordMessage->uid = uuid7();
    }
  }

  public function updating(DiscordMessage $discordMessage): void
  {
    if (empty($discordMessage->uid)) {
      $discordMessage->uid = uuid7();
    }
  }

  public function created(DiscordMessage $discordMessage): void
  {
    $this->_log('Created', $discordMessage);
  }

  public function updated(DiscordMessage $discordMessage): void
  {
    $this->_log('Updated', $discordMessage);
  }

  public function deleted(DiscordMessage $discordMessage): void
  {
    $this->_log('Deleted', $discordMessage);
  }

  public function restored(DiscordMessage $discordMessage): void
  {
    $this->_log('Restored', $discordMessage);
  }

  public function forceDeleted(DiscordMessage $discordMessage): void
  {
    $this->_log('Force Deleted', $discordMessage);
  }

  private function _log(string $event, DiscordMessage $discordMessage): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Discord Message',
      'subject_type' => DiscordMessage::class,
      'subject_id'   => $discordMessage->id,
    ], $discordMessage);
  }
}
