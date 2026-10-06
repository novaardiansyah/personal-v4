<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackupSchedules\RelationManagers;

use App\Filament\Resources\Backups\Tables\BackupsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class BackupsRelationManager extends RelationManager
{
  protected static string $relationship = 'backups';

  public function table(Table $table): Table
  {
    $table = BackupsTable::configure($table);

    $columns = $table->getColumns();
    unset($columns['schedule.name']);

    return $table
      ->columns($columns)
      ->defaultSort('created_at', 'desc');
  }
}
