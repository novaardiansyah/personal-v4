<?php

namespace App\Filament\Resources\AiAssistantContexts\Schemas;

use App\Services\AiAssistant\AiToolRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiAssistantContextForm
{
  public static function configure(Schema $schema): Schema
  {
    $toolOptions = app(AiToolRegistry::class)->getToolOptions();

    return $schema
      ->components([
        Section::make('Persona Details')
          ->columnSpanFull()
          ->schema([
            Grid::make(['default' => 1, 'md' => 2])
              ->schema([
                TextInput::make('name')
                  ->label('Name')
                  ->required()
                  ->maxLength(255),
                TextInput::make('default_model')
                  ->label('Default Model')
                  ->default('general-chat')
                  ->required()
                  ->maxLength(255),
                TextInput::make('temperature')
                  ->label('Temperature')
                  ->numeric()
                  ->step(0.05)
                  ->minValue(0)
                  ->maxValue(2)
                  ->default(0.30)
                  ->required(),
                TextInput::make('max_tokens')
                  ->label('Max Tokens')
                  ->numeric()
                  ->minValue(256)
                  ->maxValue(32768)
                  ->default(3072)
                  ->required(),
                TextInput::make('sort_order')
                  ->label('Sort Order')
                  ->numeric()
                  ->default(0),
                Toggle::make('is_active')
                  ->label('Active')
                  ->default(true)
                  ->inline(false),
              ]),
            Textarea::make('description')
              ->label('Description')
              ->rows(2)
              ->columnSpanFull(),
            Textarea::make('system_prompt')
              ->label('System Prompt')
              ->rows(6)
              ->required()
              ->columnSpanFull(),
            TagsInput::make('starter_prompts')
              ->label('Starter Prompt Chips')
              ->placeholder('Add quick starter prompt...')
              ->columnSpanFull(),
            CheckboxList::make('allowed_tools')
              ->label('Allowed AI Tools')
              ->helperText('Leave all unchecked to allow all registered tools.')
              ->options($toolOptions)
              ->columns(2)
              ->columnSpanFull(),
          ]),
      ]);
  }
}
