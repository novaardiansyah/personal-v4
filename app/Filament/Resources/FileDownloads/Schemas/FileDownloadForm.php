<?php

namespace App\Filament\Resources\FileDownloads\Schemas;

use App\Enums\FileDownloadStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FileDownloadForm
{
  public static function configure(Schema $schema): Schema
  {
    $file_id = getCode('file_download', false);
    $uid = Str::orderedUuid()->toString();
    $download_url = getSetting('portfolio_url') . '/files/d/' . $uid;

    return $schema
      ->components([
        Section::make('')
          ->schema([
            TextInput::make('code')
              ->label('File ID')
              ->disabled()
              ->default($file_id),
            TextInput::make('uid')
              ->label('UID')
              ->readOnly()
              ->required()
              ->default($uid),
            TextInput::make('download_url')
              ->label('Download URL')
              ->readOnly()
              ->required()
              ->default($download_url),
          ])
          ->columnSpan(2),

        Section::make('')
          ->schema([
            Select::make('status')
              ->options(FileDownloadStatus::class)
              ->default('active')
              ->required()
              ->native(false),
            TextInput::make('download_count')
              ->disabled()
              ->default(0),
            TextInput::make('access_count')
              ->disabled()
              ->default(0),
          ])
          ->columnSpan(1)
      ])
      ->columns(3);
  }
}
