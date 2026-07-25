<?php

namespace App\Filament\Resources\BackupSchedules\Schemas;

use App\Enums\BackupScheduleIntervalUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupScheduleForm
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make()
					->description('Schedule Information')
					->collapsible()
					->columnSpan(['sm' => 3, 'md' => 2])
					->schema([
						Grid::make(['sm' => 1, 'xs' => 1])
							->columnSpanFull()
							->schema([
								TextInput::make('uid')
									->label('UID')
									->placeholder('Auto-generated')
									->disabled()
									->dehydrated(false)
									->visible(fn($record) => filled($record?->uid)),
								TextInput::make('filename_pattern')
									->label('Filename Pattern')
									->required()
									->default('backup-{Ymd-His}')
									->live(onBlur: true)
									->hint(fn(?string $state): string => static::previewFilenamePattern($state)),
								TextInput::make('source_path')
									->label('Source Path')
									->required()
									->placeholder('/path/to/source'),
								TextInput::make('destination_path')
									->label('Destination Path')
									->required()
									->default('./backups')
									->placeholder('/path/to/destination'),
							]),
					]),

				Section::make()
					->description('Interval & Retention')
					->collapsible()
					->columnSpan(['sm' => 3, 'md' => 1])
					->columns(1)
					->schema([
						TextInput::make('interval_value')
							->label('Interval Value')
							->numeric()
							->required()
							->default(3)
							->minValue(1),
						Select::make('interval_unit')
							->label('Interval Unit')
							->options(BackupScheduleIntervalUnit::class)
							->native(false)
							->preload()
							->required()
							->default(BackupScheduleIntervalUnit::Days),
						TextInput::make('retention_days')
							->label('Retention Days')
							->numeric()
							->required()
							->default(3)
							->minValue(1),
						Toggle::make('is_enabled')
							->label('Enabled')
							->default(true),
					]),
			])
			->columns(3);
	}

	private static function previewFilenamePattern(?string $pattern): string
	{
		if (empty($pattern)) {
			return '-';
		}

		$preview = preg_replace_callback('/\{([^}]+)\}/', function ($matches) {
			try {
				return now()->format($matches[1]);
			} catch (\Throwable $e) {
				return $matches[0];
			}
		}, $pattern);

		return $preview . '.zip';
	}
}
