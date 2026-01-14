<?php 


namespace App\Filament\Resources\EmailTemplates\Schemas;

use App\Models\EmailTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;

class EmailTemplateAction
{
  public static function protected(): Action
  {
    return Action::make('protected')
      ->label('Protected')
      ->icon('heroicon-o-lock-closed')
      ->color('success')
      ->modalHeading('Protected Template')
      ->modalDescription('Enter alias to protect this template')
      ->modalWidth(Width::Medium)
      ->visible(fn(EmailTemplate $record): bool => !$record->is_protected)
      ->fillForm(function (EmailTemplate $record): array {
        return $record->toArray();
      })
      ->schema([
        TextInput::make('alias')
          ->label('Alias')
          ->required()
          ->maxLength(255)
          ->unique('email_templates', 'alias'),
        Textarea::make('notes')
          ->label('Notes')
          ->maxLength(1000)
          ->rows(3),
      ])
      ->action(function (EmailTemplate $record, Action $action, array $data) {
        $record->update([
          'is_protected' => true,
          'alias'        => $data['alias'],
          'notes'        => $data['notes'],
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

  public static function preview(): Action
  {
    return Action::make('preview')
      ->label('Preview email')
      ->icon('heroicon-o-envelope')
      ->color('primary')
      ->url(fn(EmailTemplate $record): string => route('admin.email-templates.preview', $record))
      ->openUrlInNewTab();
  }

  public static function replicate(): Action 
  {
    return ReplicateAction::make('replicate')
      ->label('Replicate')
      ->icon('heroicon-o-document-duplicate')
      ->color('warning')
      ->action(function (EmailTemplate $record, Action $action) {
        $newRecord = $record->replicate(['is_protected', 'alias']);
        $newRecord->subject = $record->subject . ' (Copy)';

        $newRecord->save();

        $action->success();
        $action->successNotificationTitle('Email template replicated successfully');
      })
      ->requiresConfirmation()
      ->modalHeading('Replicate Email Template')
      ->modalDescription('Are you sure you want to replicate this email template?');
  }
}
