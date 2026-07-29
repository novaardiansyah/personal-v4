<?php

namespace App\Filament\Resources\BackupJobs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupJobInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Job Information')
            ->schema([
              TextEntry::make('backupSchedule.name')
                ->label('Backup Schedule')
                ->placeholder('N/A'),
              TextEntry::make('status')
                ->label('Status')
                ->badge(),
              TextEntry::make('message')
                ->label('Message')
                ->placeholder('N/A')
                ->columnSpanFull(),
            ])
            ->columns(['xl' => 2, '2xl' => 2]),

          Section::make('')
            ->description('Execution Timestamps')
            ->schema([
              TextEntry::make('assigned_at')
                ->label('Assigned At')
                ->dateTime('M d, Y H:i:s')
                ->sinceTooltip()
                ->placeholder('N/A'),
              TextEntry::make('started_at')
                ->label('Started At')
                ->dateTime('M d, Y H:i:s')
                ->sinceTooltip()
                ->placeholder('N/A'),
              TextEntry::make('finished_at')
                ->label('Finished At')
                ->dateTime('M d, Y H:i:s')
                ->sinceTooltip()
                ->placeholder('N/A'),
            ])
            ->columns(['xl' => 3, '2xl' => 3]),
        ])
          ->columnSpan(['sm' => 3, 'md' => 2]),

        Section::make('')
          ->description('System Information')
          ->schema([
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
          ->columns(1)
          ->columnSpan(['sm' => 3, 'md' => 1]),
      ])
      ->columns(3);
  }
}
