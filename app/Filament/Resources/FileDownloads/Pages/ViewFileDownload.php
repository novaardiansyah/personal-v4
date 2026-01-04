<?php

namespace App\Filament\Resources\FileDownloads\Pages;

use App\Filament\Resources\FileDownloads\FileDownloadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFileDownload extends ViewRecord
{
    protected static string $resource = FileDownloadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
