<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Gallery;
use App\Models\Payment;
use App\Services\AttachmentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditPayment extends EditRecord
{
  protected static string $resource = PaymentResource::class;

  protected function mutateFormDataBeforeFill(array $data): array
  {
    $data['old_attachments'] = $data['attachments'] ?? [];
    return $data;
  }

  protected function mutateFormDataBeforeSave(array $data): array
  {
    $record = $this->record;
    
    $newAttachments = $data['attachments'] ?? [];
    $oldAttachments = $data['old_attachments'] ?? [];

    $removedAttachments = array_diff($oldAttachments, $newAttachments);
    $addedAttachments = array_diff($newAttachments, $oldAttachments);

    if (!empty($removedAttachments)) {
      foreach ($removedAttachments as $attachment) {
        AttachmentService::deleteAttachmentFiles($attachment);
      }
    }

    if (!empty($addedAttachments)) {
      foreach ($addedAttachments as $attachment) {
        $file = $attachment;

        $gallery = Gallery::create([
          'file_path'     => $file,
          'subject_id'    => $record->id,
          'subject_type'  => Payment::class,
          'has_optimized' => true,
        ]);

        $optimized = uploadAndOptimize($file, 'public', 'images/payment');

        foreach ($optimized as $key => $image) {
          if ($key === 'original')
            continue;

          $gallery = $gallery->replicate();

          $gallery->file_path = $image;
          $gallery->has_optimized = false;

          $gallery->save();
        }
      }
    }

    return $data;
  }

  protected function getHeaderActions(): array
  {
    return [
      ViewAction::make(),
      DeleteAction::make(),
      ForceDeleteAction::make(),
      RestoreAction::make(),
    ];
  }

  #[On('refreshForm')]
  public function refreshForm(): void
  {
    $data = $this->record->toArray();
    parent::refreshFormData(array_keys($data));
  }

  protected function getRedirectUrl(): string
  {
    $resource = static::getResource();
    return $resource::getUrl('index');
  }
}
