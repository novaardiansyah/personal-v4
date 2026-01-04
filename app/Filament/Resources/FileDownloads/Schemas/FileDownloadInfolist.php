<?php

namespace App\Filament\Resources\FileDownloads\Schemas;

use App\Models\FileDownload;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FileDownloadInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('File information')
          ->schema([
            TextEntry::make('code')
              ->label('File ID')
              ->badge()
              ->copyable(),
            TextEntry::make('uid')
              ->label('UID')
              ->copyable(),
            TextEntry::make('download_url')
              ->label('Download URL')
              ->copyable(),
            
            TextEntry::make('status')
              ->label('Status')
              ->badge()
              ->formatStateUsing(fn(FileDownload $record) => $record->status->label())
              ->color(fn(FileDownload $record) => $record->status->color()),
            TextEntry::make('download_count')
              ->numeric()
              ->label('Download Count')
              ->badge(),
            TextEntry::make('access_count')
              ->numeric()
              ->label('Access Count')
              ->badge(),
          ])
          ->columns(3),

        Section::make('')
          ->description('Timestamps information')
          ->schema([
            TextEntry::make('created_at')
              ->dateTime()
              ->sinceTooltip()
              ->label('Created'),
            TextEntry::make('updated_at')
              ->dateTime()
              ->sinceTooltip()
              ->label('Updated'),
            TextEntry::make('deleted_at')
              ->dateTime()
              ->sinceTooltip(),
          ])
          ->columns(3),
      ])
      ->columns(1);
  }
}
