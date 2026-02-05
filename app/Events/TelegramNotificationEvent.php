<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelegramNotificationEvent
{
  use Dispatchable, SerializesModels;

  public string $message;
  public string $event;
  public array $properties;

  public function __construct(string $message, string $event, array $properties = [])
  {
    $this->message = $message;
    $this->event = $event;
    $this->properties = $properties;
  }
}
