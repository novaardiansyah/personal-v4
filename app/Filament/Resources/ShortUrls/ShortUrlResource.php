<?php

namespace App\Filament\Resources\ShortUrls;

use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\ImageColumn;
use Str;
use UnitEnum;
use App\Filament\Resources\ShortUrls\Pages\ManageShortUrls;
use App\Models\ShortUrl;
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
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShortUrlResource extends Resource
{
  protected static ?string $model = ShortUrl::class;
  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedLink;
  protected static string | UnitEnum | null $navigationGroup = 'Productivity';
  protected static ?int $navigationSort = 10;
  protected static ?string $recordTitleAttribute = 'note';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('long_url')
          ->required()
          ->label('Long URL')
          ->url()
          ->prefixIcon('heroicon-o-link'),

        Textarea::make('note')
          ->label('Note')
          ->default(null)
          ->rows(3)
          ->placeholder('Optional note for this short URL'),

        Hidden::make('str_code')
          ->default(Str::random(7)),

        Placeholder::make('preview')
          ->label('Preview')
          ->content(function (callable $get) {
            $domain = getSetting('short_url_domain');
            return "{$domain}/" . $get('str_code');
          }),

        Toggle::make('is_active')
          ->required()
          ->default(true),
      ])
      ->columns(1);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('code'),
        TextEntry::make('note'),
        TextEntry::make('long_url'),
        TextEntry::make('short_code'),
        TextEntry::make('tiny_url'),
        IconEntry::make('is_active')
          ->boolean(),
        TextEntry::make('clicks')
          ->numeric(),
        ImageEntry::make('qrcode')
          ->size(100)
          ->disk('public'),

        Grid::make([
          'default' => 3
        ])
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
        ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('note')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('code')
          ->searchable()
          ->copyable(),
        ImageColumn::make('qrcode')
          ->size(50)
          ->disk('public')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('note')
          ->searchable(),
        TextColumn::make('long_url')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('short_code')
          ->searchable()
          ->copyable()
          ->limit(100),
        IconColumn::make('is_active')
          ->boolean(),
        TextColumn::make('clicks')
          ->numeric(),
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
            ->modalWidth(Width::Medium)
            ->mutateRecordDataUsing(function (ShortUrl $record, array $data): array {
              $data['str_code'] = $record->getCleanShortCode();
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
      'index' => ManageShortUrls::route('/'),
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
