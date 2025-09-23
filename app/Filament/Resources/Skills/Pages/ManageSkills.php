<?php

namespace App\Filament\Resources\Skills\Pages;

use App\Filament\Resources\Skills\SkillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageSkills extends ManageRecords
{
    protected static string $resource = SkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::Medium),
        ];
    }
}
