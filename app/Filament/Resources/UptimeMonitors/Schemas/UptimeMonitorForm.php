<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorForm.php
 * Created Date: Saturday February 7th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\UptimeMonitors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UptimeMonitorForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('General information')
          ->collapsible()
          ->columns(2)
          ->columnSpan(2)
          ->schema([
            TextInput::make('url')
              ->url()
              ->required(),
            TextInput::make('name')
              ->default(null),
          ]),

        Section::make()
          ->description('Status information')
          ->collapsible()
          ->columns(1)
          ->schema([
            TextInput::make('interval')
              ->required()
              ->numeric()
              ->default(60)
              ->suffix('seconds')
              ->minValue(60)
              ->live(onBlur: true)
              ->hint(fn(?string $state): string => secondsToHumanReadable((int) $state)),
            Toggle::make('is_active')
              ->required()
              ->default(true)
              ->inlineLabel(false),
          ]),
      ])
      ->columns(3);
  }
}
