<?php

namespace App\Filament\Resources\Generates;

use App\Filament\Resources\Generates\Pages\CreateGenerate;
use App\Filament\Resources\Generates\Pages\EditGenerate;
use App\Filament\Resources\Generates\Pages\ListGenerates;
use App\Filament\Resources\Generates\Pages\ViewGenerate;
use App\Filament\Resources\Generates\Schemas\GenerateForm;
use App\Filament\Resources\Generates\Schemas\GenerateInfolist;
use App\Filament\Resources\Generates\Tables\GeneratesTable;
use App\Models\Generate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class GenerateResource extends Resource
{
  protected static ?string $model = Generate::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;
  protected static string|UnitEnum|null $navigationGroup = 'Settings';
  protected static ?int $navigationSort = 20;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return GenerateForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return GenerateInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return GeneratesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListGenerates::route('/'),
      'create' => CreateGenerate::route('/create'),
      'view'   => ViewGenerate::route('/{record}'),
      'edit'   => EditGenerate::route('/{record}/edit'),
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
