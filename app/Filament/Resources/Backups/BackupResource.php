<?php

/*
 * Project Name: personal-v4
 * File: BackupResource.php
 * Created Date: October 02, 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Backups;

use App\Filament\Resources\Backups\Pages\ListBackups;
use App\Filament\Resources\Backups\Pages\ViewBackup;
use App\Filament\Resources\Backups\Schemas\BackupInfolist;
use App\Filament\Resources\Backups\Tables\BackupsTable;
use App\Models\Backup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

  public static function canCreate(): bool
  {
    return false;
  }

  public static function canEdit(Model $record): bool
  {
    return false;
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
      'index' => ListBackups::route('/'),
      'view'  => ViewBackup::route('/{record}'),
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
