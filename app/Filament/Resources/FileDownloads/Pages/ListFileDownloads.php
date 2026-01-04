<?php

namespace App\Filament\Resources\FileDownloads\Pages;

use App\Filament\Resources\FileDownloads\FileDownloadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFileDownloads extends ListRecords
{
    protected static string $resource = FileDownloadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
