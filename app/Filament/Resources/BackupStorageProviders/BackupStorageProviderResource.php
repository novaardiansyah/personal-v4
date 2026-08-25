<?php

namespace App\Filament\Resources\BackupStorageProviders;

use App\Filament\Resources\BackupStorageProviders\Pages\CreateBackupStorageProvider;
use App\Filament\Resources\BackupStorageProviders\Pages\EditBackupStorageProvider;
use App\Filament\Resources\BackupStorageProviders\Pages\ListBackupStorageProviders;
use App\Filament\Resources\BackupStorageProviders\Pages\ViewBackupStorageProvider;
use App\Filament\Resources\BackupStorageProviders\Schemas\BackupStorageProviderForm;
use App\Filament\Resources\BackupStorageProviders\Schemas\BackupStorageProviderInfolist;
use App\Filament\Resources\BackupStorageProviders\Tables\BackupStorageProvidersTable;
use App\Models\BackupStorageProvider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BackupStorageProviderResource extends Resource
{
  protected static ?string $model = BackupStorageProvider::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

  protected static string|UnitEnum|null $navigationGroup = 'Backup';

  protected static ?int $navigationSort = 5;

  protected static ?string $navigationLabel = 'Storage Providers';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return BackupStorageProviderForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return BackupStorageProviderInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return BackupStorageProvidersTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListBackupStorageProviders::route('/'),
      'create' => CreateBackupStorageProvider::route('/create'),
      'view'   => ViewBackupStorageProvider::route('/{record}'),
      'edit'   => EditBackupStorageProvider::route('/{record}/edit'),
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
