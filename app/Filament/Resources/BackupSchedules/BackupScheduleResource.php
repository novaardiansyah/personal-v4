<?php

namespace App\Filament\Resources\BackupSchedules;

use App\Filament\Resources\BackupSchedules\Pages\CreateBackupSchedule;
use App\Filament\Resources\BackupSchedules\Pages\EditBackupSchedule;
use App\Filament\Resources\BackupSchedules\Pages\ListBackupSchedules;
use App\Filament\Resources\BackupSchedules\Pages\ViewBackupSchedule;
use App\Filament\Resources\BackupSchedules\RelationManagers\JobsRelationManager;
use App\Filament\Resources\BackupSchedules\Schemas\BackupScheduleForm;
use App\Filament\Resources\BackupSchedules\Schemas\BackupScheduleInfolist;
use App\Filament\Resources\BackupSchedules\Tables\BackupSchedulesTable;
use App\Models\BackupSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BackupScheduleResource extends Resource
{
  protected static ?string $model = BackupSchedule::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowUp;

  protected static string|UnitEnum|null $navigationGroup = 'Backup';

  protected static ?int $navigationSort = 1;

  protected static ?string $navigationLabel = 'Schedules';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return BackupScheduleForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return BackupScheduleInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return BackupSchedulesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      JobsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListBackupSchedules::route('/'),
      'create' => CreateBackupSchedule::route('/create'),
      'view'   => ViewBackupSchedule::route('/{record}'),
      'edit'   => EditBackupSchedule::route('/{record}/edit'),
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
