<?php

namespace App\Filament\Resources\Files\Pages;

use App\Filament\Resources\Files\FileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use App\Enums\FileType;

class EditFile extends EditRecord
{
	protected static string $resource = FileResource::class;

	protected function getHeaderActions(): array
	{
		return [
			ViewAction::make(),
			DeleteAction::make(),
			ForceDeleteAction::make(),
			RestoreAction::make(),
		];
	}

	protected function mutateFormDataBeforeFill(array $data): array
	{
		if ($data['type_id'] == FileType::DeviceFile->value) {
			$data['file_path_text'] = $data['file_path'];
		}

		return $data;
	}

	protected function mutateFormDataBeforeSave(array $data): array
	{
		if ($data['type_id'] == FileType::DeviceFile->value) {
			$data['file_path'] = $data['file_path_text'];
			unset($data['file_path_text']);
		}

		return $data;
	}
}
