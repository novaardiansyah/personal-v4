<?php

namespace App\Filament\Resources\Backups\Actions;

use App\Jobs\BackupResource\PruneBackupsByDateRangeJob;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class PruneBackupAction
{
  public static function make(): Action
  {
    return Action::make('prune')
      ->label('Prune Backups')
      ->icon(Heroicon::OutlinedTrash)
      ->color('danger')
      ->modalHeading('Prune Backups')
      ->modalDescription('Specify the date range to prune backups. All matching backups will be permanently removed from the database and storage.')
      ->modalSubmitActionLabel('Prune')
      ->modalIcon(Heroicon::OutlinedTrash)
      ->modalWidth(Width::Small)
      ->schema([
        DatePicker::make('start_date')
          ->label('Start Date')
          ->required()
          ->native(false)
          ->displayFormat('M d, Y')
          ->default(now()->subDays(14)->toDateString())
          ->closeOnDateSelection(),
        DatePicker::make('end_date')
          ->label('End Date')
          ->required()
          ->native(false)
          ->displayFormat('M d, Y')
          ->closeOnDateSelection()
          ->default(now()->subDays(3)->toDateString())
          ->afterOrEqual('start_date'),
      ])
      ->action(function (array $data): void {
        PruneBackupsByDateRangeJob::dispatch(
          $data['start_date'],
          $data['end_date'],
          getUser(),
        );

        Notification::make()
          ->title('Backup Pruning Queued')
          ->body('The backup pruning process has been dispatched to the queue.')
          ->info()
          ->send();
      });
  }
}
