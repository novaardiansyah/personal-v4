<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DiscordServer;

class DiscordServerObserver
{
  public function created(DiscordServer $discordServer): void
  {
    $this->_log('Created', $discordServer);
  }

  public function updated(DiscordServer $discordServer): void
  {
    $this->_log('Updated', $discordServer);
  }

  public function deleted(DiscordServer $discordServer): void
  {
    $this->_log('Deleted', $discordServer);
  }

  public function restored(DiscordServer $discordServer): void
  {
    $this->_log('Restored', $discordServer);
  }

  public function forceDeleted(DiscordServer $discordServer): void
  {
    $this->_log('Force Deleted', $discordServer);
  }

  private function _log(string $event, DiscordServer $discordServer): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Discord Server',
      'subject_type' => DiscordServer::class,
      'subject_id'   => $discordServer->id,
    ], $discordServer);
  }
}
