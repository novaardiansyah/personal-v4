<?php

namespace App\Filament\Resources\AiAssistantContexts\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AiAssistantContextsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('sort_order')
          ->label('#')
          ->sortable(),
        TextColumn::make('name')
          ->label('Persona')
          ->searchable()
          ->sortable(),
        TextColumn::make('default_model')
          ->label('Model')
          ->badge(),
        TextColumn::make('temperature')
          ->label('Temp')
          ->numeric(2),
        TextColumn::make('max_tokens')
          ->label('Max Tokens')
          ->numeric(),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
        TextColumn::make('updated_at')
          ->label('Updated')
          ->dateTime('Y-m-d H:i')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort('sort_order')
      ->filters([
        TrashedFilter::make(),
      ])
      ->recordActions([
        ActionGroup::make([
          EditAction::make(),
          DeleteAction::make(),
          RestoreAction::make(),
          ForceDeleteAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          RestoreBulkAction::make(),
          ForceDeleteBulkAction::make(),
        ]),
      ]);
  }
}
