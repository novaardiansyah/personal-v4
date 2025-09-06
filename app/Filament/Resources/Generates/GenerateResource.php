<?php

namespace App\Filament\Resources\Generates;

use App\Filament\Resources\Generates\Pages\ManageGenerates;
use App\Models\Generate;
use BackedEnum;
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
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GenerateResource extends Resource
{
  protected static ?string $model = Generate::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->required()
          ->maxLength(255),
        TextInput::make('alias')
          ->required()
          ->maxLength(25),
        
        Grid::make([
          'default' => 4
        ])
        ->schema([
          TextInput::make('prefix')
            ->required()
            ->maxLength(5)
            ->suffix('-')
            ->live(onBlur: true)
            ->afterStateUpdated(fn (callable $set, callable $get) => static::getReviewID($set, $get)),
          TextInput::make('separator')
            ->readOnly()
            ->default(now()->format('ymd')),
          TextInput::make('queue')
            ->required()
            ->numeric()
            ->minValue(1)
            ->default(1)
            ->maxValue(999999)
            ->live(onBlur: true)
            ->afterStateUpdated(fn (callable $set, callable $get) => static::getReviewID($set, $get)),
          TextInput::make('next_id')
            ->label('Preview')
            ->disabled(),
        ])
        ->columnSpanFull()
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('name'),
        TextEntry::make('alias')
          ->copyable()
          ->badge()
          ->color('info'),
        TextEntry::make('prefix')
          ->badge()
          ->color('info'),
        TextEntry::make('separator')
          ->badge()
          ->color('info'),
        TextEntry::make('queue')
          ->badge()
          ->color('info')
          ->numeric(),
        TextEntry::make('review')
          ->copyable()
          ->badge()
          ->color('info')
          ->state(fn (Generate $record) => $record->getNextId()),

        Grid::make([
          'default' => 3
        ])
        ->schema([
          TextEntry::make('created_at')
            ->dateTime(),
          TextEntry::make('updated_at')
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
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('alias')
          ->searchable()
          ->toggleable()
          ->copyable()
          ->badge()
          ->color('info'),
        TextColumn::make('prefix')
          ->searchable()
          ->toggleable()
          ->badge()
          ->color('info'),
        TextColumn::make('separator')
          ->searchable()
          ->toggleable()
          ->badge()
          ->color('info'),
        TextColumn::make('queue')
          ->numeric()
          ->sortable()
          ->toggleable()
          ->badge()
          ->color('info'),
        TextColumn::make('review')
          ->copyable()
          ->badge()
          ->color('info')
          ->toggleable()
          ->state(fn (Generate $record) => $record->getNextId()),
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
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->filters([
        TrashedFilter::make()
          ->native(false)
          ->preload()
          ->searchable(),
      ])
      ->defaultSort('updated_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),

          EditAction::make()
            ->mutateRecordDataUsing(function (array $data, Generate $record) {
              $data['next_id'] = $record->getNextId();
              return $data;
            }),

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
      'index' => ManageGenerates::route('/'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }

  public static function getReviewID(callable $set, callable $get): void
  {
    $prefix    = $get('prefix');
    $separator = $get('separator');
    $queue     = $get('queue');

    if (!$prefix || !$separator || !$queue) return;

    $res = $prefix . '-' . substr($separator, 0, 4) . str_pad($queue, 4, '0', STR_PAD_LEFT) . substr($separator, 4, 2);

    $set('next_id', $res);
  }
}
