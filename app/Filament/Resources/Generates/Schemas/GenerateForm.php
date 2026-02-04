<?php

namespace App\Filament\Resources\Generates\Schemas;

use App\Filament\Resources\Generates\Actions\GenerateAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GenerateForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->columns(2)
          ->description('Configure the format')
          ->collapsible()
          ->schema([
            TextInput::make('prefix')
              ->required()
              ->maxLength(5)
              ->live(onBlur: true)
              ->afterStateUpdated(fn(callable $set, callable $get) => self::handleReviewID($set, $get)),
            TextInput::make('separator')
              ->readOnly()
              ->default(now()->format('ymd')),
            TextInput::make('queue')
              ->required()
              ->numeric()
              ->minValue(1)
              ->default(1)
              ->maxValue(999999)
              ->live(onBlur: true)
              ->afterStateUpdated(fn(callable $set, callable $get) => self::handleReviewID($set, $get)),
            TextInput::make('next_id')
              ->label('Preview')
              ->disabled(),
          ]),

        Section::make()
          ->columns(2)
          ->description('Basic information about the generate')
          ->collapsible()
          ->schema([
            TextInput::make('name')
              ->required()
              ->maxLength(255)
              ->live(onBlur: true)
              ->afterStateUpdated(fn(callable $set, callable $get) => self::handleAlias($set, $get)),
            TextInput::make('alias')
              ->required()
              ->maxLength(25),
          ]),
      ]);
  }

  public static function handleReviewID(callable $set, callable $get): void
  {
    $prefix    = $get('prefix');
    $separator = $get('separator');
    $queue     = $get('queue');

    $result = GenerateAction::getReviewID($prefix, $separator, $queue);

    if ($result) {
      $set('next_id', $result);
    }
  }

  public static function handleAlias(callable $set, callable $get): void
  {
    $name = $get('name');

    if ($name) {
      $set('alias', str()->slug($name, '_'));
    }
  }
}
