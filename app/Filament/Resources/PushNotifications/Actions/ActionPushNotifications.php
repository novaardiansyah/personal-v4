<?php

namespace App\Filament\Resources\PushNotifications\Actions;

use App\Models\PushNotification;
use App\Models\User;
use Filament\Notifications\Notification;

class ActionPushNotifications
{
  /**
   * Send push notification action for a PushNotification record
   */
  public static function sendPushNotification(PushNotification $record): void
  {
    $user = User::find($record->user_id);

    if (!$user || !$user->has_allow_notification) {
      Notification::make()
        ->danger()
        ->title('Cannot Send Notification')
        ->body('User has disabled notifications')
        ->send();
      return;
    }

    if (!$user->notification_token) {
      Notification::make()
        ->danger()
        ->title('Cannot Send Notification')
        ->body('No notification token found for this user')
        ->send();
      return;
    }

    $result = sendPushNotification($user, $record);

    if ($result['success']) {
      Notification::make()
        ->success()
        ->title('Notification Sent Successfully')
        ->body("Push notification sent to {$user->name}")
        ->send();
    } else {
      Notification::make()
        ->danger()
        ->title('Failed to Send Notification')
        ->body($result['message'])
        ->send();
    }
  }
}
