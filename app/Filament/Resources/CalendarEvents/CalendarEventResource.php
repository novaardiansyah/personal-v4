<?php

namespace App\Filament\Resources\CalendarEvents;

use App\Filament\Resources\CalendarEvents\Pages\CreateCalendarEvent;
use App\Filament\Resources\CalendarEvents\Pages\EditCalendarEvent;
use App\Filament\Resources\CalendarEvents\Pages\ListCalendarEvents;
use App\Filament\Resources\CalendarEvents\Pages\ViewCalendarEvent;
use App\Filament\Resources\CalendarEvents\RelationManagers\TodosRelationManager;
use App\Filament\Resources\CalendarEvents\Schemas\CalendarEventForm;
use App\Filament\Resources\CalendarEvents\Schemas\CalendarEventInfolist;
use App\Filament\Resources\CalendarEvents\Tables\CalendarEventsTable;
use App\Models\CalendarEvent;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CalendarEventResource extends Resource
{
  protected static ?string $model = CalendarEvent::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;
  protected static string|UnitEnum|null $navigationGroup = 'Calendar';
  protected static ?int $navigationSort = 2;
  protected static ?string $recordTitleAttribute = 'title';

  public static function form(Schema $schema): Schema
  {
    return CalendarEventForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return CalendarEventsTable::configure($table);
  }

  public static function infolist(Schema $schema): Schema
  {
    return CalendarEventInfolist::configure($schema);
  }

  public static function getRelations(): array
  {
    return [
      TodosRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListCalendarEvents::route('/'),
      'create' => CreateCalendarEvent::route('/create'),
      'view'   => ViewCalendarEvent::route('/{record}'),
      'edit'   => EditCalendarEvent::route('/{record}/edit'),
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
