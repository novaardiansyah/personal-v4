<?php

namespace App\Filament\Resources\BackupJobs\RelationManagers;

use App\Filament\Resources\Backups\BackupResource;
use App\Filament\Resources\Backups\Tables\BackupsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class BackupsRelationManager extends RelationManager
{
  protected static string $relationship = 'backups';

  protected static ?string $relatedResource = BackupResource::class;

  public function table(Table $table): Table
  {
    return BackupsTable::configure($table);
  }
}
