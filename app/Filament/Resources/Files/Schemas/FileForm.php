<?php

namespace App\Filament\Resources\Files\Schemas;

use App\Enums\FileType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('type_id')
          ->relationship('type', 'name')
          ->searchable()
          ->preload()
          ->live()
          ->default(FileType::LocalFile->value)
          ->disabledOn('edit')
					->dehydrated()
          ->required(),

        TextInput::make('uid')
          ->label('Unique ID')
          ->maxLength(255)
          ->default(fn(): string => uuid7())
          ->readOnly()
          ->required(),

        TextInput::make('file_name')
          ->label('File Name')
          ->visible(fn(Get $get): bool => (int) ($get('type_id') ?? FileType::LocalFile->value) !== FileType::LocalFile->value)
          ->maxLength(255),

        TextInput::make('file_path_text')
          ->label('File Path')
          ->visible(fn(Get $get): bool => (int) ($get('type_id') ?? FileType::LocalFile->value) !== FileType::LocalFile->value)
          ->maxLength(255),

        TextInput::make('file_size')
          ->label('File Size')
          ->visible(fn(Get $get): bool => (int) ($get('type_id') ?? FileType::LocalFile->value) !== FileType::LocalFile->value)
          ->maxLength(255)
          ->live(onBlur: true)
          ->formatStateUsing(fn($state): ?string => $state !== null && $state !== '' ? sizeFormat((float) $state) : null)
          ->dehydrateStateUsing(fn($state): int => parseSizeToBytes($state))
          ->suffix(fn(?string $state): string => sizeFormat(parseSizeToBytes($state))),

        TextInput::make('file_alias')
          ->label('Display Name')
          ->maxLength(255),

        DateTimePicker::make('scheduled_deletion_time')
          ->label('Expiry Date')
          ->default(now()->addMonth())
          ->native(false)
          ->visible(fn(Get $get): bool => (int) ($get('type_id') ?? FileType::LocalFile->value) === FileType::LocalFile->value),

        Textarea::make('description')
          ->label('Description')
          ->columnSpanFull(),

        FileUpload::make('file_path')
          ->label('File Upload')
          ->visible(fn(Get $get): bool => (int) ($get('type_id') ?? FileType::LocalFile->value) === FileType::LocalFile->value)
          ->required(fn(Get $get): bool => (int) ($get('type_id') ?? FileType::LocalFile->value) === FileType::LocalFile->value)
          ->disk('public')
          ->directory('attachments')
          ->moveFiles()
          ->imageEditor()
          ->getUploadedFileNameForStorageUsing(
            fn(TemporaryUploadedFile $file): string => uuid7() . '.' . $file->getClientOriginalExtension()
          )
          ->columnSpanFull(),
      ]);
  }
}
