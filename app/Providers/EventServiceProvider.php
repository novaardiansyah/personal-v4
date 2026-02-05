<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use App\Listeners\LogUserLogin;
use App\Events\TelegramNotificationEvent;
use App\Listeners\LogTelegramNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
  protected $listen = [
    Login::class => [
      LogUserLogin::class,
    ],
    TelegramNotificationEvent::class => [
      LogTelegramNotification::class,
    ],
  ];

  /**
   * Register any events for your application.
   *
   * @return void
   */
  public function boot()
  {
    //
  }
}
