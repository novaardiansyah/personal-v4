<?php

namespace App\Filament\Resources\CalendarCategories;

use App\Filament\Resources\CalendarCategories\Pages\CreateCalendarCategory;
use App\Filament\Resources\CalendarCategories\Pages\EditCalendarCategory;
use App\Filament\Resources\CalendarCategories\Pages\ManageCalendarCategories;
use App\Filament\Resources\CalendarCategories\Schemas\CalendarCategoryForm;
use App\Filament\Resources\CalendarCategories\Tables\CalendarCategoriesTable;
use App\Models\CalendarCategory;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CalendarCategoryResource extends Resource
{
  protected static ?string $model = CalendarCategory::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
  protected static string|UnitEnum|null $navigationGroup = 'Calendar';
  protected static ?int $navigationSort = 1;
  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return CalendarCategoryForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return CalendarCategoriesTable::configure($table);
  }

  public static function getPages(): array
  {
    return [
      'index'  => ManageCalendarCategories::route('/'),
      'create' => CreateCalendarCategory::route('/create'),
      'edit'   => EditCalendarCategory::route('/{record}/edit'),
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
