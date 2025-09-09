<?php

namespace App\Filament\Resources\Items;

use App\Models\ItemType;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use UnitEnum;

use App\Filament\Resources\Items\Pages\ManageItems;
use App\Models\Item;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
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

class ItemResource extends Resource
{
  protected static ?string $model = Item::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

  protected static ?int $navigationSort = 10;
  
  protected static string | UnitEnum | null $navigationGroup = 'Items';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('type_id')
          ->relationship('type', 'name')
          ->default(ItemType::PRODUCT)
          ->native(false)
          ->preload()
          ->required(),
        TextInput::make('name')
          ->required()
          ->maxLength(255),
        TextInput::make('amount')
          ->required()
          ->numeric()
          ->integer()
          ->default(0),
      ])
        ->columns(1);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('code'),
        TextEntry::make('type_id')
          ->label('Type')
          ->badge()
          ->color(fn (string $state): string => match ((int) $state) {
              ItemType::PRODUCT => 'primary',
              ItemType::SERVICE => 'info',
              default => 'primary'
          })
          ->formatStateUsing(fn (Item $record) => $record->type->name ?? 'Unknown'),
        TextEntry::make('name'),
        TextEntry::make('amount')
          ->numeric()
          ->formatStateUsing(fn (string $state) => toIndonesianCurrency((int) $state ?? 0)),

        Grid::make([
          'default' => 3
        ])
          ->schema([
            TextEntry::make('created_at')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->sinceTooltip()
              ->dateTime(),
            TextEntry::make('deleted_at')
              ->dateTime(),
          ])
          ->columnSpanFull()
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
        TextColumn::make('code')
          ->searchable()
          ->toggleable(),
        TextColumn::make('type_id')
          ->label('Type')
          ->toggleable()
          ->badge()
          ->color(fn (string $state): string => match ((int) $state) {
              ItemType::PRODUCT => 'primary',
              ItemType::SERVICE => 'info',
              default => 'primary'
          })
          ->formatStateUsing(fn (Item $record) => $record->type->name ?? 'Unknown'),
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('amount')
          ->numeric()
          ->sortable()
          ->toggleable()
          ->formatStateUsing(fn (string $state) => toIndonesianCurrency((int) $state ?? 0, showCurrency: Setting::showPaymentCurrency())),
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
      'index' => ManageItems::route('/'),
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
