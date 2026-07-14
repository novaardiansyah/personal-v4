<?php

namespace App\Jobs;

use App\Models\CalendarReminder;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCalendarReminderJob implements ShouldQueue
{
  use Queueable;

  public function __construct(
    public CalendarReminder $reminder
  ) {}

  public function handle(): void
  {
    $reminder = $this->reminder->fresh();

    if ($reminder->reminded_at) {
      return;
    }

    $user = $reminder->user;
    $event = $reminder->event;
    $todo = $reminder->todo;

    if ($event) {
      $title = 'Event Reminder: ' . $event->title;
      $body = 'Your event starts soon: ' . $event->title;
    } else {
      $title = 'Todo Reminder: ' . $todo->title;
      $body = 'Your todo is due soon: ' . $todo->title;
    }

    Notification::make()
      ->title($title)
      ->body($body)
      ->icon('heroicon-o-bell')
      ->iconColor('info')
      ->sendToDatabase($user);

    sendTelegramNotification($body, ['user_id' => $user->id]);

    $reminder->update(['reminded_at' => now()]);

    saveActivityLog([
      'log_name'     => 'Notification',
      'event'        => 'Calendar Reminder Sent',
      'description'  => 'Calendar reminder sent: ' . $title,
      'subject_type' => CalendarReminder::class,
      'subject_id'   => $reminder->id,
    ]);
  }
}
