<?php

namespace App\Filament\Resources\ShortUrls\Pages;

use App\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ActionShortUrl 
{
  public static function generateQr()
  {
    return Action::make('generate_qr')
      ->label('Generate QR')
      ->icon('heroicon-s-qr-code')
      ->color('warning')
      ->requiresConfirmation()
      ->modalHeading('Generate QR Code')
      ->modalDescription('Are you sure you want to generate the QR code for this short URL?')
      ->action(function (Action $action, ShortUrl $record) {
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
      });
  }
}