<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Filament\Resources\Settings\Schemas\SettingAction;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SettingsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('key')
          ->label('Alias')
          ->badge()
          ->searchable()
          ->copyable()
          ->toggleable(),
        TextColumn::make('value')
          ->searchable()
          ->toggleable()
          ->badge(),
        TextColumn::make('description')
          ->label('Description')
          ->limit(50)
          ->searchable()
          ->toggleable(),
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
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->filters([
        TrashedFilter::make(),
      ])
      ->recordAction('change_value')
      ->recordUrl(null)
      ->recordActions([
        ActionGroup::make([
          EditAction::make()
            ->modalWidth(Width::Medium),
          
          Action::make('change_value')
            ->label('Change Value')
            ->icon('heroicon-o-arrows-right-left')
            ->modalWidth(Width::ExtraLarge)
            ->modalHeading('Change setting value')
            ->color('primary')
            ->form(fn (Schema $form) => SettingAction::formChangeValue($form))
            ->fillForm(fn (Setting $record): array => SettingAction::fillFormChangeValue($record))
            ->action(fn (Action $action, Setting $record, array $data) => SettingAction::actionChangeValue($action, $record, $data)),

          ReplicateAction::make('replicate')
            ->label('Replicate')
            ->icon('heroicon-s-document-duplicate')
            ->color('warning')
            ->action(function (Setting $record, Action $action) {
              $newRecord = $record->replicate();
              $newRecord->key .=  '_copy';
              $newRecord->name .=  ' (Copy)';

              $newRecord->save();

              $action->success();
              $action->successNotificationTitle('Setting replicated successfully');
            })
            ->requiresConfirmation()
            ->modalHeading('Replicate Setting')
            ->modalDescription('Are you sure you want to replicate this setting?'),

          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ])
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
