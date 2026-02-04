<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use App\Models\ActivityLog;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ActivityLogInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          TextEntry::make('causer.name')
            ->label('Causer'),

          TextEntry::make('subject_type')
            ->label('Subject')
            ->formatStateUsing(function ($state, ActivityLog $record) {
              if (!$state) return '-';
              return Str::of($state)->afterLast('\\')->headline() . ' # ' . $record->subject_id;
            }),

          TextEntry::make('created_at')
            ->dateTime()
            ->sinceTooltip(),

          TextEntry::make('log_name')
            ->label('Group')
            ->badge()
            ->formatStateUsing(fn($state) => ucwords($state)),

          TextEntry::make('event')
            ->label('Event')
            ->badge()
            ->color(fn($state) => ActivityLog::getEventColor($state)),

          TextEntry::make('description')
            ->label('Description')
            ->wrap()
            ->limit(300)
            ->columnSpanFull(),
        ])
          ->description('General information')
          ->collapsible()
          ->columns(3),

        Section::make([
          TextEntry::make('ip_address'),

          TextEntry::make('timezone'),

          TextEntry::make('geolocation'),

          TextEntry::make('country'),

          TextEntry::make('city'),

          TextEntry::make('region'),

          TextEntry::make('postal'),

          TextEntry::make('user_agent')
            ->columnSpan(2),
        ])
          ->description('Location and client information')
          ->collapsible()
          ->visible(
            fn(ActivityLog $record): bool =>
            !!$record->ip_address
          )
          ->columns(3),

        Section::make([
          KeyValueEntry::make('properties_str')
            ->label('Properties')
            ->hidden(fn($state) => !$state),

          KeyValueEntry::make('prev_properties_str')
            ->label('Previous properties')
            ->hidden(fn($state) => !$state),
        ])
          ->description('Properties information')
          ->collapsible()
          ->visible(
            fn(ActivityLog $record): bool =>
            !empty($record->properties_str) ||
              !empty($record->prev_properties_str)
          )
      ])
      ->columns(1);
  }
}
