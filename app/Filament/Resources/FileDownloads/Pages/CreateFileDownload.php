<?php

namespace App\Filament\Resources\FileDownloads\Pages;

use App\Filament\Resources\FileDownloads\FileDownloadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFileDownload extends CreateRecord
{
    protected static string $resource = FileDownloadResource::class;
}
