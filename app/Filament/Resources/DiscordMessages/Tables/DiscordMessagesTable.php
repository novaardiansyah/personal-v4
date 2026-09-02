<?php

namespace App\Filament\Resources\DiscordMessages\Tables;

use App\Enums\DiscordMessageStatus;
use App\Models\DiscordMessage;
use App\Models\DiscordWebhook;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DiscordMessagesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('uid')
          ->label('UID')
          ->searchable()
          ->badge()
          ->color('info')
          ->limit(8)
          ->copyable()
          ->copyableState(fn($state) => $state)
          ->tooltip(fn($state) => $state)
          ->toggleable(),
        TextColumn::make('webhook.name')
          ->label('Webhook')
          ->formatStateUsing(fn(DiscordMessage $record) => $record->webhook ? "{$record->webhook->name} (" . ($record->webhook->channel?->name ?? '-') . ")" : '-')
          ->badge()
          ->color('info')
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('content')
          ->label('Content')
          ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_SLASHES) : $state)
          ->limit(50)
          ->toggleable(),
        TextColumn::make('status')
          ->label('Status')
          ->badge()
          ->searchable()
          ->sortable()
          ->toggleable(),
        TextColumn::make('response')
          ->label('Response')
          ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_SLASHES) : $state)
          ->limit(50)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: false),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        SelectFilter::make('webhook_id')
          ->label('Webhook')
          ->relationship('webhook', 'name', fn($query) => $query->with('channel'))
          ->getOptionLabelFromRecordUsing(fn(DiscordWebhook $record) => "{$record->name} (" . ($record->channel?->name ?? '-') . ")")
          ->searchable()
          ->preload()
          ->native(false),
        SelectFilter::make('status')
          ->label('Status')
          ->options(DiscordMessageStatus::class)
          ->native(false),
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('created_at', 'desc')
      ->recordAction(null)
      ->recordUrl(null)
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make(),
          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          ForceDeleteBulkAction::make(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }
}
