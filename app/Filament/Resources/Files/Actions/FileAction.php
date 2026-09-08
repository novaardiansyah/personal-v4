<?php

namespace App\Filament\Resources\Files\Actions;

use App\Filament\Resources\Files\FileResource;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class FileAction
{
  public static function detailsBulk(): BulkAction
  {
    return BulkAction::make('details')
      ->label('Details')
      ->icon(Heroicon::OutlinedEye)
      ->color('primary')
      ->deselectRecordsAfterCompletion()
      ->action(function (Collection $records, $livewire) {
        $ids = $records->pluck('id')->implode(',');
        $livewire->redirect(FileResource::getUrl('details', ['ids' => $ids]), navigate: true);
      });
  }
}
