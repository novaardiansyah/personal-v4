<?php

namespace App\Filament\Resources\BackupSchedules\RelationManagers;

use App\Filament\Resources\BackupJobs\BackupJobResource;
use App\Filament\Resources\BackupJobs\Tables\BackupJobsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class JobsRelationManager extends RelationManager
{
  protected static string $relationship = 'backupJobs';

  protected static ?string $relatedResource = BackupJobResource::class;

  public function table(Table $table): Table
  {
    $table = BackupJobsTable::configure($table);

    $columns = $table->getColumns();
    unset($columns['backupSchedule.name']);

    return $table
      ->columns($columns);
  }
}
