<?php

namespace App\Filament\Resources\Backups;

use App\Filament\Resources\Backups\Pages\CreateBackup;
use App\Filament\Resources\Backups\Pages\EditBackup;
use App\Filament\Resources\Backups\Pages\ListBackups;
use App\Filament\Resources\Backups\Pages\ViewBackup;
use App\Filament\Resources\Backups\Schemas\BackupForm;
use App\Filament\Resources\Backups\Schemas\BackupInfolist;
use App\Filament\Resources\Backups\Tables\BackupsTable;
use App\Models\Backup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BackupResource extends Resource
{
  protected static ?string $model = Backup::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

  protected static string|UnitEnum|null $navigationGroup = 'Backup';

  protected static ?int $navigationSort = 3;

  protected static ?string $navigationLabel = 'Backups';

  protected static ?string $recordTitleAttribute = 'uid';

  public static function form(Schema $schema): Schema
  {
    return BackupForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return BackupInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return BackupsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListBackups::route('/'),
      'create' => CreateBackup::route('/create'),
      'view'   => ViewBackup::route('/{record}'),
      'edit'   => EditBackup::route('/{record}/edit'),
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
