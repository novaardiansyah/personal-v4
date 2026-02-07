<?php

namespace App\Filament\Resources\UptimeMonitors;

use App\Filament\Resources\UptimeMonitors\Pages\CreateUptimeMonitor;
use App\Filament\Resources\UptimeMonitors\Pages\EditUptimeMonitor;
use App\Filament\Resources\UptimeMonitors\Pages\ListUptimeMonitors;
use App\Filament\Resources\UptimeMonitors\Pages\ViewUptimeMonitor;
use App\Filament\Resources\UptimeMonitors\Schemas\UptimeMonitorForm;
use App\Filament\Resources\UptimeMonitors\Schemas\UptimeMonitorInfolist;
use App\Filament\Resources\UptimeMonitors\Tables\UptimeMonitorsTable;
use App\Models\UptimeMonitor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UptimeMonitorResource extends Resource
{
  protected static ?string $model = UptimeMonitor::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

  protected static string|UnitEnum|null $navigationGroup = 'Productivity';

  protected static ?int $navigationSort = 9;

  protected static ?string $recordTitleAttribute = 'name';

  public static function getModelLabel(): string
  {
    return 'Uptime Monitor';
  }

  public static function getPluralModelLabel(): string
  {
    return 'Uptime Monitors';
  }

  public static function form(Schema $schema): Schema
  {
    return UptimeMonitorForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return UptimeMonitorInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return UptimeMonitorsTable::configure($table);
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
      'index'  => ListUptimeMonitors::route('/'),
      'create' => CreateUptimeMonitor::route('/create'),
      'view'   => ViewUptimeMonitor::route('/{record}'),
      'edit'   => EditUptimeMonitor::route('/{record}/edit'),
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
