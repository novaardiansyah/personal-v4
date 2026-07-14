<?php

namespace App\Filament\Resources\CalendarTodos;

use App\Filament\Resources\CalendarTodos\Pages\CreateCalendarTodo;
use App\Filament\Resources\CalendarTodos\Pages\EditCalendarTodo;
use App\Filament\Resources\CalendarTodos\Pages\ListCalendarTodos;
use App\Filament\Resources\CalendarTodos\Pages\ViewCalendarTodo;
use App\Filament\Resources\CalendarTodos\RelationManagers\EventRelationManager;
use App\Filament\Resources\CalendarTodos\Schemas\CalendarTodoForm;
use App\Filament\Resources\CalendarTodos\Schemas\CalendarTodoInfolist;
use App\Filament\Resources\CalendarTodos\Tables\CalendarTodosTable;
use App\Models\CalendarTodo;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CalendarTodoResource extends Resource
{
  protected static ?string $model = CalendarTodo::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;
  protected static string|UnitEnum|null $navigationGroup = 'Calendar';
  protected static ?int $navigationSort = 3;
  protected static ?string $recordTitleAttribute = 'title';

  public static function form(Schema $schema): Schema
  {
    return CalendarTodoForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return CalendarTodosTable::configure($table);
  }

  public static function infolist(Schema $schema): Schema
  {
    return CalendarTodoInfolist::configure($schema);
  }

  public static function getRelations(): array
  {
    return [
      EventRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListCalendarTodos::route('/'),
      'create' => CreateCalendarTodo::route('/create'),
      'view'   => ViewCalendarTodo::route('/{record}'),
      'edit'   => EditCalendarTodo::route('/{record}/edit'),
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
