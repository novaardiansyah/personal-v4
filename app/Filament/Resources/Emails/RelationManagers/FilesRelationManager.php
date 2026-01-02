<?php

namespace App\Filament\Resources\Emails\RelationManagers;

use App\Filament\Resources\Files\FileResource;
use App\Models\File;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FilesRelationManager extends RelationManager
{
  protected static string $relationship = 'files';

  protected static ?string $relatedResource = FileResource::class;

  protected static ?string $title = 'Attachments';

  public function isReadOnly(): bool
  {
    return false;
  }

  public function table(Table $table): Table
  {
    return $table
      ->modifyQueryUsing(fn($query) => $query->withoutGlobalScopes([SoftDeletingScope::class]))
      ->recordTitleAttribute('file_name')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('file_name')
          ->label('File')
          ->tooltip(fn(File $record): string => $record->has_been_deleted ? 'File already removed' : 'Download File')
          ->url(fn(File $record): string|null => !$record->has_been_deleted ? $record->download_url : null, fn(File $record): bool => !$record->has_been_deleted)
          ->searchable()
          ->toggleable(),
        IconColumn::make('has_been_deleted')
          ->boolean()
          ->toggleable(),
        TextColumn::make('scheduled_deletion_time')
          ->dateTime()
          ->sortable()
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
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View File Details')
            ->modalWidth(Width::FourExtraLarge)
            ->slideOver()
            ->infolist(fn(Schema $infolist) => FileResource::infolist($infolist)),

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
