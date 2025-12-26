<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Gallery;
use App\Models\Payment;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
  protected static string $resource = PaymentResource::class;

  protected function afterCreate(): void
  {
    $record = $this->record;
    $attachments = $record->attachments ?? [];

    if (!empty($attachments)) {
      foreach ($attachments as $attachment) {
        $file = $attachment;

        $gallery = Gallery::create([
          'file_path'     => $file,
          'subject_id'    => $record->id,
          'subject_type'  => Payment::class,
          'has_optimized' => true,
        ]);

        $optimized = uploadAndOptimize($file, 'public', 'images/payment');

        foreach ($optimized as $key => $image) {
          if ($key === 'original') continue;

          $gallery = $gallery->replicate();

          $gallery->file_path     = $image;
          $gallery->has_optimized = false;

          $gallery->save();
        }
      }
    }
  }
  
  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    $record = $this->getRecord();

    if ($record->has_items)
      return $resource::getUrl('edit', ['record' => $record]);

    return $resource::getUrl('index');
  }
}
