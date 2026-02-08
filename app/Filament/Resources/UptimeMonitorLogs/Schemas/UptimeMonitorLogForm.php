<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorLogForm.php
 * Created Date: Sunday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\UptimeMonitorLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UptimeMonitorLogForm
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
            Select::make('uptime_monitor_id')
              ->label('Monitor')
              ->relationship('uptimeMonitor', 'name')
              ->required()
              ->searchable()
              ->preload(),
            DateTimePicker::make('checked_at')
              ->label('Checked At'),
          ]),

        Section::make()
          ->description('Response details')
          ->collapsible()
          ->columns(2)
          ->schema([
            TextInput::make('status_code')
              ->label('Status Code')
              ->numeric()
              ->default(null),
            TextInput::make('response_time_ms')
              ->label('Response Time')
              ->required()
              ->numeric()
              ->default(0)
              ->suffix('ms'),
            Toggle::make('is_healthy')
              ->label('Healthy')
              ->required()
              ->default(false)
              ->inlineLabel(false),
            Textarea::make('error_message')
              ->label('Error Message')
              ->default(null)
              ->columnSpanFull(),
          ]),
      ])
      ->columns(3);
  }
}
