<?php

namespace App\Filament\Resources\BackupSchedules\Schemas;

use App\Enums\BackupScheduleIntervalUnit;
use App\Enums\BackupType;
use App\Models\BackupSchedule;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

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
								TextInput::make('name')
									->label('Name')
									->required(),
								Select::make('type')
									->label('Type')
									->options([
										BackupType::Files->value    => BackupType::Files->getLabel(),
										BackupType::Database->value => BackupType::Database->getLabel(),
									])
									->native(false)
									->preload()
									->required()
									->default(BackupType::Files)
									->live(),
								TextInput::make('filename_pattern')
									->label('Filename Pattern')
									->required()
									->default('backup-{Ymd-His}')
									->live(onBlur: true)
									->hint(fn(?string $state): string => BackupSchedule::generateFilename($state)),
								TextInput::make('source_path')
									->label('Source Path')
									->placeholder('/www/wwwroot')
									->datalist(['/www/wwwroot'])
									->autocomplete(false)
									->required(fn(Get $get): bool => !static::isDatabaseType($get('type')))
									->visible(fn(Get $get): bool => !static::isDatabaseType($get('type'))),
								TextInput::make('drivers')
									->label('Drivers')
									->placeholder('mysql')
									->datalist(['mysql', 'pgsql', 'sqlite', 'sqlsrv'])
									->autocomplete(false)
									->required(fn(Get $get): bool => static::isDatabaseType($get('type')))
									->visible(fn(Get $get): bool => static::isDatabaseType($get('type'))),
								TextInput::make('database_name')
									->label('Database Name')
									->placeholder('my_database')
									->required(fn(Get $get): bool => static::isDatabaseType($get('type')))
									->visible(fn(Get $get): bool => static::isDatabaseType($get('type'))),
								TagsInput::make('exclude')
									->label('Exclude Files/Directories')
									->placeholder('Enter directory or file patterns, separated by enter.')
									->separator(',')
									->helperText('Press Enter to add a new option.')
									->visible(fn(Get $get): bool => !static::isDatabaseType($get('type'))),
								TextInput::make('local_destination_path')
									->label('Local Destination Path')
									->placeholder('/www/backup/sysadmin/others')
									->datalist(['/www/backup/sysadmin/others', '/www/backup/sysadmin/projects', '/www/backup/sysadmin/database'])
									->autocomplete(false)
									->required(),
								Grid::make(3)
									->schema([
										Toggle::make('keep_local_backup')
											->label('Keep Local Backup')
											->default(false),
										Toggle::make('is_sync_cloud')
											->label('Sync Cloud')
											->default(false)
											->live()
											->afterStateUpdated(fn(Set $set, $state) => ! $state ? $set('r2_destination_path', null) : null),
									]),
								TextInput::make('r2_destination_path')
									->label('Cloud Destination Path')
									->placeholder('/backups/others')
									->datalist(['/backups/projects', '/backups/database', '/backups/others'])
									->autocomplete(false)
									->visible(fn(Get $get): bool => (bool) $get('is_sync_cloud')),
							]),
					]),

				Section::make()
					->description('Interval & Limits')
					->collapsible()
					->columnSpan(['sm' => 3, 'md' => 1])
					->columns(1)
					->schema([
						TextInput::make('interval_value')
							->label('Interval Value')
							->numeric()
							->required()
							->default(3)
							->minValue(1)
							->live(onBlur: true)
							->afterStateUpdated(fn(Get $get, Set $set) => static::updateNextBackupAt($get, $set)),
						Select::make('interval_unit')
							->label('Interval Unit')
							->options(BackupScheduleIntervalUnit::class)
							->native(false)
							->preload()
							->required()
							->default(BackupScheduleIntervalUnit::Days)
							->live()
							->afterStateUpdated(fn(Get $get, Set $set) => static::updateNextBackupAt($get, $set)),
						DateTimePicker::make('next_backup_at')
							->label('Next Backup At')
							->native(false)
							->default(fn() => now()->addDays(3)->format('Y-m-d H:i:s')),
						TextInput::make('count_backup')
							->label('Backup Count')
							->numeric()
							->default(0)
							->disabled()
							->dehydrated(false),
						TextInput::make('sum_file_size')
							->label('Total File Size')
							->formatStateUsing(fn($state) => sizeFormat(floatval($state)))
							->disabled()
							->dehydrated(false),
						TextInput::make('max_count_backup')
							->label('Max Backup Count')
							->numeric()
							->required()
							->default(5)
							->minValue(1),
						Toggle::make('is_enabled')
							->label('Enabled')
							->default(true),
					]),
			])
			->columns(3);
	}

	private static function updateNextBackupAt(Get $get, Set $set): void
	{
		$value = (int) $get('interval_value');
		$unit  = $get('interval_unit');

		if ($value <= 0 || empty($unit)) {
			return;
		}

		$unitEnum = $unit instanceof BackupScheduleIntervalUnit
			? $unit
			: BackupScheduleIntervalUnit::tryFrom((string) $unit);

		if (! $unitEnum) {
			return;
		}

		$lastBackupAt = $get('last_backup_at');
		$baseDate     = $lastBackupAt ? Carbon::parse($lastBackupAt) : now();

		$nextDate = match ($unitEnum) {
			BackupScheduleIntervalUnit::Minutes => $baseDate->addMinutes($value),
			BackupScheduleIntervalUnit::Hours   => $baseDate->addHours($value),
			BackupScheduleIntervalUnit::Days    => $baseDate->addDays($value),
			BackupScheduleIntervalUnit::Weeks   => $baseDate->addWeeks($value),
			BackupScheduleIntervalUnit::Months  => $baseDate->addMonths($value),
		};

		$set('next_backup_at', $nextDate->format('Y-m-d H:i:s'));
	}

	private static function isDatabaseType(mixed $type): bool
	{
		if (empty($type)) {
			return false;
		}

		if ($type instanceof BackupType) {
			return $type === BackupType::Database;
		}

		return (string) $type === 'database' || (string) $type === BackupType::Database->value;
	}
}
