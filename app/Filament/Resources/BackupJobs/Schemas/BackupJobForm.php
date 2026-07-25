<?php

namespace App\Filament\Resources\BackupJobs\Schemas;

use App\Enums\BackupJobStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupJobForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Job Information')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 2])
          ->schema([
            Grid::make(['sm' => 1, 'xs' => 1])
              ->columnSpanFull()
              ->schema([
                Select::make('backup_schedule_id')
                  ->label('Backup Schedule')
                  ->relationship('backupSchedule', 'name')
                  ->searchable()
                  ->preload()
                  ->required()
                  ->native(false),
                Select::make('status')
                  ->label('Status')
                  ->options(BackupJobStatus::class)
                  ->default(BackupJobStatus::Pending)
                  ->required()
                  ->native(false),
                Textarea::make('message')
                  ->label('Message')
                  ->rows(4)
                  ->placeholder('Job execution message or error details...'),
              ]),
          ]),

        Section::make()
          ->description('Execution Timestamps')
          ->collapsible()
          ->columnSpan(['sm' => 3, 'md' => 1])
          ->columns(1)
          ->schema([
            DateTimePicker::make('assigned_at')
              ->label('Assigned At')
              ->native(false),
            DateTimePicker::make('started_at')
              ->label('Started At')
              ->native(false),
            DateTimePicker::make('finished_at')
              ->label('Finished At')
              ->native(false),
          ]),
      ])
      ->columns(3);
  }
}
