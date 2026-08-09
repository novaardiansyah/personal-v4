<?php

namespace App\Filament\Resources\BackupSchedules\Widgets;

use App\Enums\BackupStatus;
use App\Models\Backup;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BackupOverviewWidget extends BaseWidget
{
  protected static bool $isDiscovered = false;

  protected function getStats(): array
  {
    $totalFiles  = Backup::count();
    $totalSize   = (float) Backup::sum('file_size');
    $totalFailed = Backup::where('status', BackupStatus::Failed->value)
      ->orWhere('status', BackupStatus::Failed)
      ->count();

    return [
      Stat::make('Total Files Backup', number_format($totalFiles))
        ->description('Total recorded backup files')
        ->icon(Heroicon::OutlinedFolderOpen)
        ->color('primary'),

      Stat::make('Total Size Backup', sizeFormat($totalSize))
        ->description('Cumulative size of backups')
        ->icon(Heroicon::OutlinedArchiveBox)
        ->color('info'),

      Stat::make('Total Backup Failed', (string) $totalFailed)
        ->description($totalFailed > 0 ? 'Failed backup jobs requiring attention' : 'No failed backups')
        ->icon(Heroicon::OutlinedXCircle)
        ->color($totalFailed > 0 ? 'danger' : 'success'),
    ];
  }
}
