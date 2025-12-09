<?php

namespace App\Filament\Resources\Notes\Pages;

use App\Filament\Resources\Notes\NoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageNotes extends ManageRecords
{
    protected static string $resource = NoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::Medium)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['code'] = getCode('note');
                    $data['user_id'] = auth()->id();
                    return $data;
                }),
        ];
    }
}
