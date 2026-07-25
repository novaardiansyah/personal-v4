<?php

namespace App\Filament\Resources\BackupSchedules\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupScheduleInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('Schedule Information')
          ->schema([
            TextEntry::make('uid')
              ->label('UID')
              ->copyable()
              ->badge()
              ->color('info'),
            TextEntry::make('filename_pattern')
              ->label('Filename Pattern')
              ->placeholder('N/A'),
            TextEntry::make('source_path')
              ->label('Source Path')
              ->copyable()
              ->placeholder('N/A'),
            TextEntry::make('destination_path')
              ->label('Destination Path')
              ->copyable()
              ->placeholder('N/A'),
            IconEntry::make('is_enabled')
              ->label('Enabled')
              ->boolean(),
          ])
          ->columns(['xl' => 2, '2xl' => 3])
          ->columnSpan(['sm' => 3, 'md' => 2]),

        Section::make('')
          ->description('Interval & Execution')
          ->schema([
            TextEntry::make('interval_value')
              ->label('Interval Value'),
            TextEntry::make('interval_unit')
              ->label('Interval Unit')
              ->badge(),
            TextEntry::make('retention_days')
              ->label('Retention Days')
              ->formatStateUsing(fn($state) => "{$state} days"),
            TextEntry::make('next_backup_at')
              ->label('Next Backup At')
              ->dateTime('M d, Y H:i')
              ->placeholder('N/A'),
            TextEntry::make('last_backup_at')
              ->label('Last Backup At')
              ->dateTime('M d, Y H:i')
              ->placeholder('N/A'),
          ])
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),

        Section::make('')
          ->description('System Information')
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->label('Last Updated')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime()
              ->placeholder('Active'),
          ])
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),
      ])
      ->columns(3);
  }
}
