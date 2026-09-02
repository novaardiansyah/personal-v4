<?php

namespace App\Filament\Pages\Schemas;

use App\Models\Backup;
use App\Models\BackupJob;
use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use App\Models\BackupStorageProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BackupSettingForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          TextInput::make('name')
            ->label('Name')
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function (Set $set, Get $get) {
              $name = $get('name') ?? '';
              $slug = Str::of($name)->slug('_');
              $set('key', $slug);
            }),

          TextInput::make('key')
            ->label('Alias')
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->helperText('Only letters, numbers, and underscores are allowed. Example: backup_retention_days, auto_backup_enabled'),

          Grid::make([
            'default' => 3
          ])
            ->schema([
              TagsInput::make('options')
                ->label('Value options')
                ->placeholder('Enter option values, separated by commas.')
                ->separator(',')
                ->visible(fn(Get $get) => $get('has_options'))
                ->helperText('Press Enter to add a new option.')
                ->live(onBlur: true)
                ->columnSpan(2),

              Toggle::make('has_options')
                ->label('Has options')
                ->inline(false)
                ->live(),
            ])
        ])
          ->columns(1)
          ->description('Setting details'),

        Section::make([
          Textarea::make('value')
            ->label('Value')
            ->required()
            ->rows(3)
            ->visible(fn(Get $get) => !$get('has_options'))
            ->maxLength(255),

          Select::make('value_option')
            ->label('Choose value')
            ->required()
            ->visible(fn(Get $get) => $get('has_options'))
            ->native(false)
            ->searchable()
            ->options(function (Get $get) {
              $options = $get('options') ?? [];
              return collect($options)->mapWithKeys(function ($option) {
                return [$option => $option];
              });
            }),

          Grid::make(2)
            ->schema([
              Select::make('subject_type')
                ->label('Subject Type')
                ->options([
                  Backup::class                => 'Backup',
                  BackupSchedule::class        => 'Backup Schedule',
                  BackupJob::class             => 'Backup Job',
                  BackupStorage::class         => 'Backup Storage',
                  BackupStorageProvider::class => 'Backup Storage Provider',
                ])
                ->default(Backup::class)
                ->required()
                ->native(false)
                ->searchable(),

              TextInput::make('subject_id')
                ->label('Subject ID')
                ->numeric(),
            ]),

          Textarea::make('description')
            ->label('Description')
            ->maxLength(1000)
            ->rows(4)
            ->placeholder('Enter a description for this setting.'),
        ])
          ->description('Other details'),
      ])
      ->columns(2);
  }
}
