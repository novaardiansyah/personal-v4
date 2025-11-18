<?php

namespace App\Filament\Resources\ShortUrls\Schemas;

use App\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ShortUrlAction
{
  public static function regenerateQRCode(Action $action, ShortUrl $record): void
  {
    try {
      $success = $record->generateQRCode();

      if ($success) {
        $action->success();

        Notification::make()
          ->success()
          ->title('QR Code Regenerated')
          ->body('QR code has been successfully regenerated for ' . $record->note)
          ->send();
      } else {
        $action->failure();

        Notification::make()
          ->danger()
          ->title('Regeneration Failed')
          ->body('Failed to regenerate QR code. Please try again.')
          ->send();
      }

    } catch (\Exception $e) {
      $action->failure();

      Notification::make()
        ->danger()
        ->title('Regeneration Failed')
        ->body('Failed to regenerate QR code: ' . $e->getMessage())
        ->send();
    }
  }
}