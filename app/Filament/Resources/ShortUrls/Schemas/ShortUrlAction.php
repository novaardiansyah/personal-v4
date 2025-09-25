<?php

namespace App\Filament\Resources\ShortUrls\Schemas;

use App\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use HeroQR\Core\QRCodeGenerator;
use Illuminate\Support\Facades\Storage;

class ShortUrlAction
{
  public static function generateQRCode(ShortUrl $record): void
  {
    $qrCodeManager = new QRCodeGenerator();
    $short_code = $record->short_code;

    $qrCode = $qrCodeManager
      ->setData($short_code)
      ->generate();

    // Delete existing QR code if it exists
    if ($record->qrcode && Storage::disk('public')->exists($record->qrcode)) {
      Storage::disk('public')->delete($record->qrcode);
    }

    $path = 'qrcodes/short-urls/' . $record->getCleanShortCode();
    $qrCode->saveTo(Storage::disk('public')->path($path));

    $record->update([
      'qrcode' => $path . '.png'
    ]);
  }

  public static function regenerateQRCode(Action $action, ShortUrl $record): void
  {
    try {
      self::generateQRCode($record);

      $action->success();

      Notification::make()
        ->success()
        ->title('QR Code Regenerated')
        ->body('QR code has been successfully regenerated for ' . $record->note)
        ->send();

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