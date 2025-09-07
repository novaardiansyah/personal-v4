<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('type_id')
          ->numeric()
          ->sortable(),
        TextColumn::make('user_id')
          ->numeric()
          ->sortable(),
        TextColumn::make('payment_account_id')
          ->numeric()
          ->sortable(),
        TextColumn::make('payment_account_to_id')
          ->numeric()
          ->sortable(),
        TextColumn::make('code')
          ->searchable(),
        TextColumn::make('amount')
          ->numeric()
          ->sortable(),
        IconColumn::make('has_items')
          ->boolean(),
        TextColumn::make('date')
          ->date()
          ->sortable(),
        IconColumn::make('is_scheduled')
          ->boolean(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort( 'date', 'desc')
      ->filters([
        TrashedFilter::make(),
      ])
      ->recordActions([
        ViewAction::make(),
        EditAction::make(),
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
