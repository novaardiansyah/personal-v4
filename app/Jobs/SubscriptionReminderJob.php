<?php

namespace App\Jobs;

use App\Mail\SubscriptionResource\SubscriptionReminderMail;
use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SubscriptionReminderJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;

  public array $backoff = [60, 300, 900];

  public function handle(): void
  {
    $today = Carbon::now()->startOfDay();

    $subscriptions = Subscription::query()
      ->where('is_paused', false)
      ->whereNotNull('next_date')
      ->whereNotNull('reminder_days_before')
      ->whereNull('deleted_at')
      ->get();

    foreach ($subscriptions as $subscription) {
      if (!$this->shouldRemind($subscription, $today)) {
        continue;
      }

      $this->sendTelegram($subscription);
      $this->sendEmail($subscription);

      $subscription->update(['last_reminded_at' => Carbon::now()]);
    }
  }

  private function shouldRemind(Subscription $subscription, Carbon $today): bool
  {
    $reminderDate = Carbon::parse($subscription->next_date)
      ->subDays($subscription->reminder_days_before)
      ->startOfDay();

    if (!$today->isSameDay($reminderDate)) {
      return false;
    }

    if (is_null($subscription->last_reminded_at)) {
      return true;
    }

    return $today->greaterThan(Carbon::parse($subscription->last_reminded_at)->startOfDay());
  }

  private function sendTelegram(Subscription $subscription): void
  {
    $date = carbonTranslatedFormat($subscription->next_date, 'd F Y', 'id');

    $message = "Pengingat Tagihan Berlangganan\n"
      . "Nama: {$subscription->name}\n"
      . "Nominal: " . toIndonesianCurrency($subscription->amount) . "\n"
      . "Jatuh Tempo: {$date}\n"
      . "Siklus: {$subscription->cycle}";

    sendTelegramNotification($message);
  }

  private function sendEmail(Subscription $subscription): void
  {
    $causer = getUser(userCode: getSetting('default_user_payment_report'));

    $data = [
      'author_name' => getSetting('author_name'),
      'subject'     => 'Pengingat Tagihan Berlangganan: ' . $subscription->name,
      'name'        => $subscription->name,
      'amount'      => toIndonesianCurrency($subscription->amount),
      'next_date'   => carbonTranslatedFormat($subscription->next_date, 'd F Y', 'id'),
      'cycle'       => $subscription->cycle,
      'created_at'  => now()->toDateTimeString(),
    ];

    Mail::to(getSetting('daily_payment_email'))->queue(new SubscriptionReminderMail($data));

    saveActivityLog([
      'log_name'    => 'Notification',
      'description' => 'Subscription Reminder by ' . $causer->name,
      'event'       => 'Mail Notification',
      'properties'  => [
        'email'   => getSetting('daily_payment_email'),
        'subject' => $data['subject'],
      ],
    ]);
  }
}
