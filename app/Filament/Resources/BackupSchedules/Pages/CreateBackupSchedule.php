<?php

namespace App\Filament\Resources\BackupSchedules\Pages;

use App\Filament\Resources\BackupSchedules\BackupScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBackupSchedule extends CreateRecord
{
	protected static string $resource = BackupScheduleResource::class;

	protected function getRedirectUrl(): string
	{
		$resource = static::getResource();
		return $resource::getUrl('index');
	}
}
