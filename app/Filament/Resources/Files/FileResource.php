<?php

namespace App\Filament\Resources\Files;

use BackedEnum;
use Filament\Actions\ActionGroup;
use UnitEnum;
use App\Filament\Resources\Files\Pages\ManageFiles;
use App\Models\File;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FileResource extends Resource
{
  protected static ?string $model = File::class;

  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedFolderOpen;
  protected static string | UnitEnum | null $navigationGroup = 'Settings';
  protected static ?int $navigationSort = 29;

  protected static ?string $recordTitleAttribute = 'file_name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        // ! Do something
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        // ! Do something
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('file_name')
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('user.name')
          ->label('User')
          ->searchable(),
        TextColumn::make('file_name')
          ->label('File')
          ->tooltip(fn(File $record): string => $record->has_been_deleted ? 'File already removed' : 'Download File')
          ->url(fn(File $record): string | null => !$record->has_been_deleted ? $record->download_url : null, fn(File $record): bool => !$record->has_been_deleted)
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
          ->toggleable(),
      ])
      ->filters([
        TrashedFilter::make(),
      ])
      ->recordActions([
        ActionGroup::make([

        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([

        ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => ManageFiles::route('/'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }
}
