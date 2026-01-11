<?php 


namespace App\Filament\Resources\EmailTemplates\Schemas;

use App\Models\EmailTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class EmailTemplateAction
{
  public static function protected(): Action
  {
    return Action::make('protected')
      ->label('Protected')
      ->icon('heroicon-o-lock-closed')
      ->color('success')
      ->requiresConfirmation()
      ->modalHeading('Protected Template')
      ->modalDescription('Are you sure you want to protect this template?')
      ->visible(fn(EmailTemplate $record): bool => !$record->is_protected)
      ->action(function (EmailTemplate $record, Action $action) {
        $record->update([
          'is_protected' => true,
        ]);

        $action->success();
        $action->successNotification(
          Notification::make()
            ->title('Protected Template')
            ->body('Template protected successfully')
            ->success()
            ->send()
        );
      });
  }

  public static function unProtected(): Action
  {
    return Action::make('unprotected')
      ->label('Unprotected')
      ->icon('heroicon-o-lock-open')
      ->color('danger')
      ->requiresConfirmation()
      ->modalHeading('Unprotected Template')
      ->modalDescription('Are you sure you want to unprotected this template?')
      ->visible(fn(EmailTemplate $record): bool => $record->is_protected)
      ->action(function (EmailTemplate $record, Action $action) {
        $record->update([
          'is_protected' => false,
        ]);

        $action->success();
        $action->successNotification(
          Notification::make()
            ->title('Unprotected Template')
            ->body('Template unprotected successfully')
            ->success()
            ->send()
        );
      });
  }

  public static function deleteBulk(): DeleteBulkAction
  {
    return DeleteBulkAction::make()
      ->action(function (Collection $records, DeleteBulkAction $action) {
        if ($records->contains('is_protected', true)) {
          $action->failure();

          $action->failureNotification(
            Notification::make()
              ->title('Failed to delete')
              ->body('Some templates are protected and cannot be deleted.')
              ->danger()
              ->send()
          );
          return;
        }

        $action->success();
        $action->successNotification(
          Notification::make()
            ->title('Templates deleted successfully')
            ->success()
            ->send()
        );
      });
  }

  public static function forceDeleteBulk(): ForceDeleteBulkAction
  {
    return ForceDeleteBulkAction::make()
      ->action(function (Collection $records, ForceDeleteBulkAction $action) {
        if ($records->contains('is_protected', true)) {
          $action->failure();

          $action->failureNotification(
            Notification::make()
              ->title('Failed to delete')
              ->body('Some templates are protected and cannot be deleted.')
              ->danger()
              ->send()
          );
          return;
        }

        $action->success();
        $action->successNotification(
          Notification::make()
            ->title('Templates deleted successfully')
            ->success()
            ->send()
        );
      });
  }
}
