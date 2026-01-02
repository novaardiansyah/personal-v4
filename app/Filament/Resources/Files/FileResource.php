<?php

namespace App\Filament\Resources\Files;

use BackedEnum;
use UnitEnum;
use Filament\Actions\ActionGroup;
use App\Filament\Resources\Files\Pages\ManageFiles;
use App\Models\File;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class FileResource extends Resource
{
  protected static ?string $model = File::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;
  protected static string|UnitEnum|null $navigationGroup = 'Settings';
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
        Section::make('')
          ->description('File Information')
          ->components([
            TextEntry::make('file_name')
              ->label('File Name'),
            TextEntry::make('file_path')
              ->label('File Path'),
            TextEntry::make('download_url')
              ->label('Download URL')
              ->url(fn(File $record): ?string => !$record->has_been_deleted ? $record->download_url : null)
              ->openUrlInNewTab(),
            IconEntry::make('has_been_deleted')
              ->label('Deleted')
              ->boolean(),
            TextEntry::make('scheduled_deletion_time')
              ->label('Scheduled Deletion')
              ->dateTime(),
          ])
          ->columns(2),
        Section::make('')
          ->description('Subject Information')
          ->components([
            TextEntry::make('subject_type')
              ->label('Subject Type')
              ->formatStateUsing(fn(?string $state): string => $state ? Str::of($state)->afterLast('\\')->headline() : '-'),
            TextEntry::make('subject_id')
              ->label('Subject ID'),
          ])
          ->columns(2),
        Section::make('')
          ->description('Timestamp Information')
          ->components([
            TextEntry::make('created_at')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('updated_at')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->dateTime()
              ->sinceTooltip(),
          ])
          ->columns(3),
      ])
      ->columns(1);
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
          ->url(fn(File $record): string|null => !$record->has_been_deleted ? $record->download_url : null, fn(File $record): bool => !$record->has_been_deleted)
          ->searchable()
          ->toggleable(),
        TextColumn::make('subject_id')
          ->label('Subject')
          ->formatStateUsing(function ($state, Model $record) {
            if (!$state)
              return;
            return Str::of($record->subject_type)->afterLast('\\')->headline() . ' # ' . $state;
          })
          ->toggleable()
          ->searchable(),
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
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('scheduled_deletion_time', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View file details')
            ->slideOver()
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
