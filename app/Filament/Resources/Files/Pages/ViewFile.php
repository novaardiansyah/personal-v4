<?php

namespace App\Filament\Resources\Files\Pages;

use App\Filament\Resources\Files\FileResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFile extends ViewRecord
{
	protected static string $resource = FileResource::class;

	protected function getHeaderActions(): array
	{
		return [
			EditAction::make(),
		];
	}
}
