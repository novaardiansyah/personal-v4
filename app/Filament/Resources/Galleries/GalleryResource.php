<?php

namespace App\Filament\Resources\Galleries;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\Galleries\Pages\ManageGalleries;
use App\Models\Gallery;
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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GalleryResource extends Resource
{
  protected static ?string $model = Gallery::class;

  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedPhoto;
  protected static string | UnitEnum | null $navigationGroup = 'Web Content';
  protected static ?int $navigationSort = 10;
  protected static ?string $recordTitleAttribute = 'description';

  public static function shouldRegisterNavigation(): bool
  {
    return false;
  }

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->columns(2)
      ->components([
        TextInput::make('url')
          ->default(null),
        Select::make('tag_id')
          ->label('Tag')
          ->relationship('tag', 'name')
          ->native(false)
          ->required(),
        FileUpload::make('image')
          ->disk('public')
          ->directory('images/gallery')
          ->image()
          ->imageEditor()
          ->required(),
        Textarea::make('description')
          ->default(null)
          ->rows(3),
        Toggle::make('is_publish')
          ->required(),
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        ImageEntry::make('image')
          ->disk('public')
          ->size(100)
          ->columnSpan(1),
        TextEntry::make('description')
          ->columnSpan(2),
          
        TextEntry::make('url')
          ->url(fn ($state) => $state)
          ->openUrlInNewTab()
          ->columnSpan(1),
        TextEntry::make('tag.name')
          ->label('Tag')
          ->badge()
          ->columnSpan(1),
        IconEntry::make('is_publish')
          ->boolean()
          ->columnSpan(1),

        TextEntry::make('created_at')
          ->dateTime()
          ->columnSpan(1),
        TextEntry::make('updated_at')
          ->sinceTooltip()
          ->dateTime()
          ->columnSpan(1),
        TextEntry::make('deleted_at')
          ->dateTime()
          ->columnSpan(1),
      ])
      ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('description')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        ImageColumn::make('image')
          ->disk('public')
          ->size(50)
          ->toggleable(),
        TextColumn::make('description')
          ->searchable()
          ->limit(50)
          ->toggleable(),
        TextColumn::make('url')
          ->searchable()
          ->copyable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('tag.name')
          ->label('Tag')
          ->badge()
          ->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('is_publish')
          ->boolean()
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
          EditAction::make(),
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
      'index' => ManageGalleries::route('/'),
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
