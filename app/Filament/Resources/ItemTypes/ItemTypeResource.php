<?php

namespace App\Filament\Resources\ItemTypes;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\Width;
use UnitEnum;

use App\Filament\Resources\ItemTypes\Pages\ManageItemTypes;
use App\Models\ItemType;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemTypeResource extends Resource
{
  protected static ?string $model = ItemType::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

  protected static ?string $recordTitleAttribute = 'name';

  protected static string | UnitEnum | null $navigationGroup = 'Items';
  protected static ?int $navigationSort = 100;

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->required(),
      ])
        ->columns(1);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('name')
          ->columnSpanFull(),
        
        TextEntry::make('created_at')
          ->dateTime(),
        TextEntry::make('updated_at')
          ->sinceTooltip()
          ->dateTime(),
        TextEntry::make('deleted_at')
          ->dateTime(),
      ])
        ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('name')
          ->searchable(),
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
      'index' => ManageItemTypes::route('/'),
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
