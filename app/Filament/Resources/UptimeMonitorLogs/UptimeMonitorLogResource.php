<?php

namespace App\Filament\Resources\UptimeMonitorLogs;

use App\Filament\Resources\UptimeMonitorLogs\Pages\CreateUptimeMonitorLog;
use App\Filament\Resources\UptimeMonitorLogs\Pages\EditUptimeMonitorLog;
use App\Filament\Resources\UptimeMonitorLogs\Pages\ListUptimeMonitorLogs;
use App\Filament\Resources\UptimeMonitorLogs\Pages\ViewUptimeMonitorLog;
use App\Filament\Resources\UptimeMonitorLogs\Schemas\UptimeMonitorLogForm;
use App\Filament\Resources\UptimeMonitorLogs\Schemas\UptimeMonitorLogInfolist;
use App\Filament\Resources\UptimeMonitorLogs\Tables\UptimeMonitorLogsTable;
use App\Models\UptimeMonitorLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UptimeMonitorLogResource extends Resource
{
  protected static ?string $model = UptimeMonitorLog::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

  protected static string|UnitEnum|null $navigationGroup = 'Logs';

  protected static ?int $navigationSort = 10;

  protected static ?string $recordTitleAttribute = 'status_code';

  public static function getModelLabel(): string
  {
    return 'Monitor Log';
  }

  public static function getPluralModelLabel(): string
  {
    return 'Monitor Logs';
  }

  public static function form(Schema $schema): Schema
  {
    return UptimeMonitorLogForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return UptimeMonitorLogInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return UptimeMonitorLogsTable::configure($table);
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
      'index'  => ListUptimeMonitorLogs::route('/'),
      'create' => CreateUptimeMonitorLog::route('/create'),
      'edit'   => EditUptimeMonitorLog::route('/{record}/edit'),
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
