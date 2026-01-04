<?php

namespace App\Filament\Resources\FileDownloads\Pages;

use App\Filament\Resources\FileDownloads\FileDownloadResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFileDownload extends EditRecord
{
    protected static string $resource = FileDownloadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
