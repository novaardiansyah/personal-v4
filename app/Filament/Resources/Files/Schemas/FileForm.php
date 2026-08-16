<?php

namespace App\Filament\Resources\Files\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FileForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('user_id')
          ->relationship('user', 'name')
          ->searchable()
          ->required(),
        Select::make('type_id')
          ->relationship('type', 'name')
          ->searchable()
          ->nullable(),
        TextInput::make('file_alias')
          ->label('Display Name')
          ->maxLength(255),
        TextInput::make('file_name')
          ->label('File Name')
          ->required()
          ->maxLength(255),
        TextInput::make('file_path')
          ->label('File Path')
          ->required()
          ->maxLength(255),
        TextInput::make('download_url')
          ->label('Download URL')
          ->url()
          ->maxLength(255),
        DateTimePicker::make('scheduled_deletion_time')
          ->label('Expiry Date'),
        Textarea::make('description')
          ->label('Description')
          ->columnSpanFull(),
      ]);
  }
}
