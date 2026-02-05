<?php

namespace App\Listeners;

use App\Events\TelegramNotificationEvent;

class LogTelegramNotification
{
  public function __construct() {}

  public function handle(TelegramNotificationEvent $event): void
  {
    $user = getUser();

    $result = $event->properties['result'] ?? [];
    unset($event->properties['result']);

    saveActivityLog([
      'log_name'   => 'Notification',
      'event'      => 'Telegram Notification',
      'causer'     => $user,
      'properties' => array_merge(['status' => $event->event, 'message' => $event->message], $event->properties, $result),
    ]);
  }
}
