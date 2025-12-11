<?php

namespace App\Filament\Resources\BlogTags;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\BlogTags\Pages\ManageBlogTags;
use App\Models\BlogTag;
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
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BlogTagResource extends Resource
{
  protected static ?string $model = BlogTag::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
  protected static string|UnitEnum|null $navigationGroup = 'Blog';
  protected static ?string $modelLabel = 'Tag';
  protected static ?string $pluralModelLabel = 'Tags';
  protected static ?int $navigationSort = 40;
  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->required()
          ->live(onBlur: true)
          ->afterStateUpdated(function (Set $set, ?string $state) {
            $set('slug', Str::slug($state));
          }),
        TextInput::make('slug')
          ->disabled()
          ->dehydrated()
          ->required(),
        Textarea::make('description')
          ->default(null)
          ->rows(3)
          ->columnSpanFull(),
      ])
      ->columns(2);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Basic Information')
          ->schema([
            TextEntry::make('name')
              ->label('Name'),
            TextEntry::make('slug')
              ->label('Slug')
              ->copyable()
              ->badge()
              ->color('info'),
            TextEntry::make('description')
              ->label('Description')
              ->markdown()
              ->prose()
              ->columnSpanFull(),
          ])
          ->columns(2),

        Section::make()
          ->description('Additional Information')
          ->schema([
            TextEntry::make('usage_count')
              ->label('Usage Count')
              ->badge()
              ->color('info')
              ->numeric(),
          ])
          ->columns(3),

        Section::make()
          ->description('Timestamps')
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->label('Updated At')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime(),
          ])
          ->columns(3)
          ->collapsible(),
      ])
      ->columns(1);
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
          ->wrap()
          ->limit(50),
        TextColumn::make('slug')
          ->searchable()
          ->copyable(),
        TextColumn::make('description')
          ->searchable()
          ->wrap()
          ->limit(100)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('usage_count')
          ->label('Usage')
          ->badge()
          ->color('info')
          ->sortable(),
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
      ->defaultSort('name', 'asc')
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
      'index' => ManageBlogTags::route('/'),
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
