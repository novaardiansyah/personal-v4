<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Payments\Schemas\PaymentAction;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Setting;
use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
  protected static string $relationship = 'items';

  protected static ?string $relatedResource = ItemResource::class;

  public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
  {
    return (bool) $ownerRecord->has_items;
  }

  public function isReadOnly(): bool
  {
    return false;
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('pivot.item_code')
          ->label('Transaction ID')
          ->searchable()
          ->toggleable(),
        TextColumn::make('type_id')
          ->label('Type')
          ->toggleable()
          ->badge()
          ->color(fn(string $state): string => match ((int) $state) {
            ItemType::PRODUCT => 'primary',
            ItemType::SERVICE => 'info',
            default => 'primary'
          })
          ->formatStateUsing(fn(Item $record) => $record->type->name ?? 'Unknown'),
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('price')
          ->numeric()
          ->toggleable()
          ->formatStateUsing(fn(string $state) => toIndonesianCurrency((int) $state ?? 0, showCurrency: Setting::showPaymentCurrency())),
        TextColumn::make('quantity')
          ->label('Qty')
          ->formatStateUsing(fn($state) => number_format($state ?? 0, 0, ',', '.'))
          ->toggleable(),
        TextColumn::make('total')
          ->label('Total')
          ->formatStateUsing(fn($state) => toIndonesianCurrency($state ?? 0, showCurrency: Setting::showPaymentCurrency()))
          ->toggleable(),
        TextColumn::make('pivot.created_at')
          ->label('Created at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('pivot.updated_at')
          ->label('Updated at')
          ->dateTime()
          ->sinceTooltip()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->defaultSort('pivot_updated_at', 'desc')
      ->headerActions([
        PaymentAction::itemCreateAction(),
        PaymentAction::attachAction(),
      ])
      ->actions([
        ActionGroup::make([
          PaymentAction::itemEditAction(),
          PaymentAction::detachAction(),
        ])
      ])
      ->bulkActions([]);
  }
}
