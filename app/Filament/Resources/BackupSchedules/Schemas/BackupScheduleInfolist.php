<?php

namespace App\Filament\Resources\BackupSchedules\Schemas;

use App\Enums\BackupType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupScheduleInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Schedule Information')
            ->collapsible()
            ->schema([
              TextEntry::make('uid')
                ->label('UID')
                ->copyable()
                ->badge()
                ->color('info'),
              TextEntry::make('name')
                ->label('Name')
                ->placeholder('N/A'),
              TextEntry::make('type')
                ->label('Type')
                ->badge(),
              TextEntry::make('drivers')
                ->label('Drivers')
                ->visible(fn($record) => $record->type === BackupType::Database || $record->type?->value === 'database')
                ->placeholder('N/A'),
              TextEntry::make('database_name')
                ->label('Database Name')
                ->visible(fn($record) => $record->type === BackupType::Database || $record->type?->value === 'database')
                ->placeholder('N/A'),
              TextEntry::make('filename_pattern')
                ->label('Filename Pattern')
                ->formatStateUsing(fn(?string $state) => $state ? static::previewFilenamePattern($state) : 'N/A')
                ->placeholder('N/A'),
              TextEntry::make('source_path')
                ->label('Source Path')
                ->copyable()
                ->placeholder('N/A'),
              TextEntry::make('include')
                ->label('Include')
                ->badge()
                ->separator(', ')
                ->placeholder('None'),
              TextEntry::make('exclude')
                ->label('Exclude')
                ->badge()
                ->separator(', ')
                ->placeholder('None'),
              TextEntry::make('local_destination_path')
                ->label('Local Destination Path')
                ->copyable()
                ->placeholder('N/A'),
              TextEntry::make('r2_destination_path')
                ->label('Cloud Destination Path')
                ->copyable()
                ->placeholder('N/A'),
            ])
            ->columns(['sm' => 2, 'lg' => 3]),

          Section::make('')
            ->description('Counts, Limits & Status')
            ->collapsible()
						->collapsed()
            ->schema([
              IconEntry::make('is_enabled')
                ->label('Enabled')
                ->boolean(),
              IconEntry::make('keep_local_backup')
                ->label('Keep Local Backup')
                ->boolean(),
              IconEntry::make('is_sync_cloud')
                ->label('Sync Cloud')
                ->boolean(),
              TextEntry::make('interval_value')
                ->label('Interval Value'),
              TextEntry::make('interval_unit')
                ->label('Interval Unit')
                ->badge(),
              TextEntry::make('count_backup')
                ->label('Backup Count'),
              TextEntry::make('max_count_backup')
                ->label('Max Backup Count'),
              TextEntry::make('sum_file_size')
                ->label('Total File Size')
                ->formatStateUsing(fn($state) => sizeFormat(floatval($state))),
            ])
            ->columns(['sm' => 2, 'lg' => 3]),
        ])
          ->columnSpan(['default' => 3, '2xl' => 2]),

        Section::make('')
          ->description('Execution & System Dates')
          ->collapsible()
					->collapsed()
          ->schema([
            TextEntry::make('next_backup_at')
              ->label('Next Backup At')
              ->dateTime('M d, Y H:i')
              ->sinceTooltip()
              ->placeholder('N/A'),
            TextEntry::make('last_backup_at')
              ->label('Last Backup At')
              ->dateTime('M d, Y H:i')
              ->sinceTooltip()
              ->placeholder('N/A'),
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('updated_at')
              ->label('Last Updated')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime()
              ->sinceTooltip()
              ->placeholder('Active'),
          ])
          ->columns(3)
          ->columnSpan(['default' => 3, '2xl' => 1]),
      ])
      ->columns(['default' => 1, '2xl' => 3]);
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
