<?php

namespace App\Filament\Resources\GalleryTags;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\GalleryTags\Pages\ManageGalleryTags;
use App\Models\GalleryTag;
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
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class GalleryTagResource extends Resource
{
  protected static ?string $model = GalleryTag::class;
  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMinus;
  protected static string | UnitEnum | null $navigationGroup = 'Web Content';
  protected static ?string $navigationParentItem = 'Galleries';
  protected static ?int $navigationSort = 20;
  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->columns(1)
      ->components([
        TextInput::make('name')
          ->required()
          ->live(onBlur: true)
          ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
        TextInput::make('slug')
          ->default(null)
          ->readOnly(),
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('name'),
        TextEntry::make('slug'),
        TextEntry::make('deleted_at')
          ->dateTime(),
        TextEntry::make('created_at')
          ->dateTime(),
        TextEntry::make('updated_at')
          ->dateTime(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('slug')
          ->searchable()
          ->copyable()
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
          ViewAction::make(),
          EditAction::make()
            ->modalWidth(Width::Medium),
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

  public static function getPages(): array
  {
    return [
      'index' => ManageGalleryTags::route('/'),
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
