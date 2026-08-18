<?php

namespace App\Filament\Resources\Files\Schemas;

use App\Models\File;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FileInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('File Information')
          ->components([
            TextEntry::make('file_name')
              ->label('File Name')
              ->copyable(),
            TextEntry::make('file_alias')
              ->label('Display Name')
              ->copyable(),
            TextEntry::make('type.name')
              ->label('Type')
              ->placeholder('-'),
            TextEntry::make('file_path')
              ->label('File Path')
              ->copyable()
              ->columnSpanFull(),
            TextEntry::make('download_url')
              ->label('Download URL')
              ->url(fn(File $record): ?string => !$record->has_been_deleted ? $record->download_url : null)
              ->openUrlInNewTab()
              ->columnSpanFull(),
            TextEntry::make('description')
              ->label('Description')
              ->placeholder('-')
              ->columnSpanFull(),
          ])
          ->columns(3),
        Section::make('')
          ->description('Subject Information')
          ->components([
            TextEntry::make('subject_id')
              ->label('Subject')
              ->formatStateUsing(function ($state, Model $record) {
                if (!$state)
                  return;
                return Str::of($record->subject_type)->afterLast('\\')->headline() . ' # ' . $state;
              }),
            IconEntry::make('has_been_deleted')
              ->label('Deleted')
              ->boolean(),
            TextEntry::make('scheduled_deletion_time')
              ->label('Scheduled Deletion')
              ->dateTime(),
          ])
          ->columns(3),
        Section::make('')
          ->description('Timestamp Information')
          ->components([
            TextEntry::make('created_at')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('updated_at')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->dateTime()
              ->sinceTooltip(),
          ])
          ->columns(3),
      ])
      ->columns(1);
  }
}
