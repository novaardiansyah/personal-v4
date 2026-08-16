<?php

namespace App\Filament\Resources\Files\Pages;

use App\Filament\Resources\Files\FileResource;
use Filament\Resources\Pages\CreateRecord;
use App\Enums\FileType;

class CreateFile extends CreateRecord
{
	protected static string $resource = FileResource::class;

	protected function mutateFormDataBeforeCreate(array $data): array
	{
		if ($data['type_id'] == FileType::DeviceFile->value) {
			$data['file_path'] = $data['file_path_text'];
			unset($data['file_path_text']);
		}

		return $data;
	}
}
