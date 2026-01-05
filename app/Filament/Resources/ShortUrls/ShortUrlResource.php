<?php

namespace App\Filament\Resources\ShortUrls;

use App\Filament\Resources\ShortUrls\Pages\ActionShortUrl;
use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Resources\ShortUrls\Pages\ManageShortUrls;
use App\Models\FileDownload;
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
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ShortUrlResource extends Resource
{
  protected static ?string $model = ShortUrl::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;
  protected static string|UnitEnum|null $navigationGroup = 'Productivity';
  protected static ?int $navigationSort = 10;
  protected static ?string $recordTitleAttribute = 'note';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Grid::make(4)
          ->schema([
            Toggle::make('is_active')
              ->required()
              ->default(true),

            Toggle::make('from_file_download')
              ->label('From File Download')
              ->required()
              ->default(false)
              ->columnSpan(2)
              ->live(onBlur: true),
          ]),

        Select::make('file_download_id')
          ->label('File Download')
          ->native(false)
          ->searchable(['uid', 'code'])
          ->relationship(
            name: 'fileDownload',
            titleAttribute: 'uid',
            modifyQueryUsing: fn($query, $record) => $query
              ->select('id', 'uid', 'code')
              ->whereNotIn(
                'id',
                ShortUrl::query()
                  ->whereNotNull('file_download_id')
                  ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                  ->pluck('file_download_id')
              ),
          )
          ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} ({$record->uid})")
          ->required(fn(Get $get) => $get('from_file_download'))
          ->visible(fn(Get $get) => $get('from_file_download'))
          ->live(onBlur: true)
          ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
            if (!$state)
              return;
            $find = FileDownload::find($state)->first();
            if ($find) {
              $set('long_url', $find->download_url);
            }
          }),

        TextInput::make('long_url')
          ->required()
          ->label('Long URL')
          ->url()
          ->readOnly(fn(Get $get) => $get('from_file_download'))
          ->maxLength(1000)
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
          ->label('Short ID')
          ->searchable()
          ->copyable()
          ->badge(),
        TextColumn::make('FileDownload.code')
          ->label('Download ID')
          ->searchable()
          ->copyable()
          ->badge()
          ->toggleable(isToggledHiddenByDefault: true),
        ImageColumn::make('qrcode')
          ->size(50)
          ->disk('public')
          ->toggleable(),
        TextColumn::make('note')
          ->wrap()
          ->limit(100)
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
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

          ActionShortUrl::generateQr(),

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
