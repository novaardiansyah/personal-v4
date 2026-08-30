<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DiscordChannel;

class DiscordChannelObserver
{
  public function created(DiscordChannel $discordChannel): void
  {
    $this->_log('Created', $discordChannel);
  }

  public function updated(DiscordChannel $discordChannel): void
  {
    $this->_log('Updated', $discordChannel);
  }

  public function deleted(DiscordChannel $discordChannel): void
  {
    $this->_log('Deleted', $discordChannel);
  }

  public function restored(DiscordChannel $discordChannel): void
  {
    $this->_log('Restored', $discordChannel);
  }

  public function forceDeleted(DiscordChannel $discordChannel): void
  {
    $this->_log('Force Deleted', $discordChannel);
  }

  private function _log(string $event, DiscordChannel $discordChannel): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Discord Channel',
      'subject_type' => DiscordChannel::class,
      'subject_id'   => $discordChannel->id,
    ], $discordChannel);
  }
}
