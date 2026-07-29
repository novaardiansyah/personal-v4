<?php

namespace App\Filament\Resources\BackupJobs;

use App\Filament\Resources\BackupJobs\Pages\CreateBackupJob;
use App\Filament\Resources\BackupJobs\Pages\EditBackupJob;
use App\Filament\Resources\BackupJobs\Pages\ListBackupJobs;
use App\Filament\Resources\BackupJobs\Pages\ViewBackupJob;
use App\Filament\Resources\BackupJobs\RelationManagers\BackupsRelationManager;
use App\Filament\Resources\BackupJobs\Schemas\BackupJobForm;
use App\Filament\Resources\BackupJobs\Schemas\BackupJobInfolist;
use App\Filament\Resources\BackupJobs\Tables\BackupJobsTable;
use App\Models\BackupJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BackupJobResource extends Resource
{
  protected static ?string $model = BackupJob::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

  protected static string|UnitEnum|null $navigationGroup = 'Backup';

  protected static ?int $navigationSort = 2;

  protected static ?string $navigationParentItem = 'Schedules';

  protected static ?string $navigationLabel = 'Jobs';

  public static function form(Schema $schema): Schema
  {
    return BackupJobForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return BackupJobInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return BackupJobsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      BackupsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListBackupJobs::route('/'),
      'create' => CreateBackupJob::route('/create'),
      'view'   => ViewBackupJob::route('/{record}'),
      'edit'   => EditBackupJob::route('/{record}/edit'),
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
