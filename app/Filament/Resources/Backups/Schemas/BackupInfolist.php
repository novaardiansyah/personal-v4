<?php

namespace App\Filament\Resources\Backups\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BackupInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Group::make([
          Section::make('')
            ->description('Backup Information')
            ->schema([
              TextEntry::make('uid')
                ->label('UID')
                ->copyable()
                ->badge()
                ->color('info'),
              TextEntry::make('file_name')
                ->label('File Name')
                ->placeholder('N/A'),
              TextEntry::make('type')
                ->label('Type')
                ->badge()
                ->placeholder('N/A'),
              TextEntry::make('status')
                ->label('Status')
                ->badge(),
              TextEntry::make('file_size')
                ->label('File Size')
                ->formatStateUsing(fn($state) => $state ? sizeFormat(floatval($state)) : 'N/A'),
              TextEntry::make('duration')
                ->label('Duration')
                ->formatStateUsing(fn($state) => "{$state}s"),
              TextEntry::make('checksum')
                ->label('Checksum')
                ->copyable()
                ->placeholder('N/A'),
              TextEntry::make('server_name')
                ->label('Server Name')
                ->placeholder('N/A'),
              TextEntry::make('file_path')
                ->label('File Path')
                ->copyable()
                ->placeholder('N/A')
                ->columnSpanFull(),
              TextEntry::make('cloud_file_path')
                ->label('Cloud File Path')
                ->copyable()
                ->placeholder('N/A')
                ->columnSpanFull(),
              TextEntry::make('message')
                ->label('Message')
                ->placeholder('N/A')
                ->columnSpanFull(),
            ])
            ->columns(['xl' => 2, '2xl' => 2]),

          Section::make('')
            ->description('Execution Timestamps')
            ->schema([
              TextEntry::make('started_at')
                ->label('Started At')
                ->dateTime('M d, Y H:i:s')
                ->sinceTooltip()
                ->placeholder('N/A'),
              TextEntry::make('completed_at')
                ->label('Completed At')
                ->dateTime('M d, Y H:i:s')
                ->sinceTooltip()
                ->placeholder('N/A'),
            ])
            ->columns(['xl' => 2, '2xl' => 2]),
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
