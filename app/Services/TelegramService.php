<?php

/*
 * Project Name: personal-v4
 * File: TelegramService.php
 * Created Date: Wednesday February 4th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Services;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class TelegramService extends Notification
{
  /**
   * Create a new class instance.
   */
  public function __construct()
  {
    //
  }

  public function via()
  {
    return ['telegram'];
  }

  public function toTelegram($notifiable)
  {
    return TelegramMessage::create()
      ->to($notifiable->telegram_id);
  }
}
