<?php

namespace App\Filament\Resources\EmailTemplates\Tables;

use App\Filament\Resources\EmailTemplates\Schemas\EmailTemplateAction;
use App\Models\EmailTemplate;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmailTemplatesTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(0)
          ->sortable(),
        TextColumn::make('code')
          ->label('Template ID')
          ->searchable()
          ->toggleable()
          ->badge()
          ->copyable(),
        TextColumn::make('alias')
          ->label('Alias')
          ->toggleable()
          ->badge()
          ->copyable(),
        TextColumn::make('subject')
          ->searchable()
          ->wrap()
          ->limit(120)
          ->toggleable(),
        IconColumn::make('is_protected')
          ->label('Protected')
          ->boolean(),
        TextColumn::make('notes')
          ->label('Notes')
          ->wrap()
          ->limit(120)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('message')
          ->searchable()
          ->wrap()
          ->limit(300)
          ->html()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make(),
          EmailTemplateAction::protected(),
          EmailTemplateAction::unProtected(),
          EmailTemplateAction::preview(),
          EmailTemplateAction::replicate(),
          DeleteAction::make()
            ->visible(fn(EmailTemplate $record): bool => !$record->is_protected),
          ForceDeleteAction::make()
            ->visible(fn(EmailTemplate $record): bool => !$record->is_protected),
          RestoreAction::make(),
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          EmailTemplateAction::deleteBulk(),
          EmailTemplateAction::forceDeleteBulk(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }
}
