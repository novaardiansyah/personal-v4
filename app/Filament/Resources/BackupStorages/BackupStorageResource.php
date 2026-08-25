<?php

namespace App\Filament\Resources\BackupStorages;

use App\Filament\Resources\BackupStorages\Pages\CreateBackupStorage;
use App\Filament\Resources\BackupStorages\Pages\EditBackupStorage;
use App\Filament\Resources\BackupStorages\Pages\ListBackupStorages;
use App\Filament\Resources\BackupStorages\Pages\ViewBackupStorage;
use App\Filament\Resources\BackupStorages\Schemas\BackupStorageForm;
use App\Filament\Resources\BackupStorages\Schemas\BackupStorageInfolist;
use App\Filament\Resources\BackupStorages\Tables\BackupStoragesTable;
use App\Models\BackupStorage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BackupStorageResource extends Resource
{
  protected static ?string $model = BackupStorage::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

  protected static string|UnitEnum|null $navigationGroup = 'Backup';

  protected static ?int $navigationSort = 4;

  protected static ?string $navigationLabel = 'Storages';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return BackupStorageForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return BackupStorageInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return BackupStoragesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListBackupStorages::route('/'),
      'create' => CreateBackupStorage::route('/create'),
      'view'   => ViewBackupStorage::route('/{record}'),
      'edit'   => EditBackupStorage::route('/{record}/edit'),
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
