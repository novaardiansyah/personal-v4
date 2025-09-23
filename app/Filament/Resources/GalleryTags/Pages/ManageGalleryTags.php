<?php

namespace App\Filament\Resources\GalleryTags\Pages;

use App\Filament\Resources\GalleryTags\GalleryTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageGalleryTags extends ManageRecords
{
    protected static string $resource = GalleryTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::Medium),
        ];
    }
}
