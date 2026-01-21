<?php

namespace App\Filament\Resources\Notes;

use App\Filament\Resources\Notes\Pages\CreateNote;
use App\Filament\Resources\Notes\Pages\EditNote;
use App\Filament\Resources\Notes\Pages\ManageNotes;
use App\Models\Note;
use BackedEnum;
use UnitEnum;
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
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NoteResource extends Resource
{
  protected static ?string $model = Note::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
  protected static string|UnitEnum|null $navigationGroup = 'Productivity';
  protected static ?int $navigationSort = 9;
  protected static ?string $recordTitleAttribute = 'title';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Note information')
          ->collapsible()
          ->columnSpan(2)
          ->schema([
            TextInput::make('title')
              ->required(),

            RichEditor::make('content')
              ->default(null)
              ->columnSpanFull(),
          ]),

        Section::make()
          ->description('Note settings')
          ->collapsible()
          ->columnSpan(1)
          ->columns(2)
          ->schema([
            Toggle::make('is_pinned')
              ->default(false),
            Toggle::make('is_archived')
              ->default(false),
          ]),
      ])
      ->columns(3);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('code')
          ->copyable(),
        TextEntry::make('title'),
        TextEntry::make('content')
          ->columnSpanFull()
          ->markdown()
          ->prose(),
        Grid::make(2)
          ->schema([
            IconEntry::make('is_pinned')
              ->boolean(),
            IconEntry::make('is_archived')
              ->boolean(),
          ]),
        Grid::make(3)
          ->columnSpanFull()
          ->schema([
            TextEntry::make('created_at')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->dateTime(),
          ]),
      ])
      ->columns(2);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('title')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('code')
          ->searchable()
          ->copyable(),
        TextColumn::make('title')
          ->searchable()
          ->wrap()
          ->limit(50),
        TextColumn::make('content')
          ->searchable()
          ->wrap()
          ->limit(100)
          ->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('is_pinned')
          ->boolean(),
        IconColumn::make('is_archived')
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

  public static function getPages(): array
  {
    return [
      'index' => ManageNotes::route('/'),
      'create' => CreateNote::route('/create'),
      'edit' => EditNote::route('/{record}/edit'),
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
