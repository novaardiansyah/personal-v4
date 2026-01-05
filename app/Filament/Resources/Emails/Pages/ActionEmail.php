<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Enums\EmailStatus;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Services\EmailResource\EmailService;
use Filament\Actions\Action;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;

class ActionEmail
{
  public static function send(): Action
  {
    return Action::make('send')
      ->action(function (Email $record, Action $action) {
        (new EmailService())->sendOrPreview($record);

        $action->success();
        $action->successNotificationTitle('Email will be sent in the background');
      })
      ->label('Send')
      ->icon('heroicon-s-paper-airplane')
      ->color('success')
      ->requiresConfirmation()
      ->modalHeading('Send Email')
      ->modalDescription('Are you sure you want to send this email?')
      ->visible(fn(Email $record): bool => $record->status === EmailStatus::Draft);
  }

  public static function preview(): Action
  {
    return Action::make('preview')
      ->label('Preview')
      ->icon('heroicon-s-eye')
      ->color('primary')
      ->url(fn(Email $record): string => route('admin.emails.preview', $record))
      ->openUrlInNewTab();
  }

  public static function replicate(): Action 
  {
    return ReplicateAction::make('replicate')
      ->label('Replicate')
      ->icon('heroicon-s-document-duplicate')
      ->color('warning')
      ->action(function (Email $record, Action $action) {
        $newRecord = $record->replicate(['files_count']);
        $newRecord->status = EmailStatus::Draft;
        $newRecord->subject = $record->subject . ' (Copy)';

        $newRecord->save();

        $action->success();
        $action->successNotificationTitle('Email replicated successfully');
      })
      ->requiresConfirmation()
      ->modalHeading('Replicate Email')
      ->modalDescription('Are you sure you want to replicate this email?');
  }

  public static function createWithTemplate(): Action
  {
    return Action::make('create_with_template')
      ->label('Template email')
      ->color('primary')
      ->modalHeading('Create email from template')
      ->slideOver()
      ->modalWidth(Width::FiveExtraLarge)
      ->schema([
        Grid::make(2)
          ->schema([
            Select::make('email_template_id')
              ->label('Email Template')
              ->native(false)
              ->searchable(['subject', 'code'])
              ->required()
              ->relationship(
                name: 'emailTemplate',
                titleAttribute: 'subject',
                modifyQueryUsing: fn($query) => $query
                  ->select('id', 'subject', 'code', 'updated_at'),
              )
              ->getOptionLabelFromRecordUsing(function(EmailTemplate $record) {
                $updated_at = carbonTranslatedFormat($record->updated_at, 'M d, Y H:i:s');
                return "{$record->code} ({$updated_at})";
              })
              ->preload()
              ->live(onBlur: true)
              ->afterStateUpdated(function (?string $state, Set $set) {
                if (!$state) {
                  $set('subject', null);
                  $set('message', null);
                  $set('placeholders', null);

                  return;
                }

                $record = EmailTemplate::find($state);

                $set('subject', $record->subject);
                $set('message', $record->message);
                $set('placeholders', $record->placeholders);
              }),

            TextInput::make('subject')
              ->default(null)
              ->required(),

            TextInput::make('email')
              ->label('Email address')
              ->email()
              ->required()
              ->live(onBlur: true)
              ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                if (!$state) {
                  $set('name', null);
                  return;
                }

                if (!$get('name')) $set('name', explode('@', $state)[0]);
              }),

            TextInput::make('name')
              ->default(null),

            RichEditor::make('message')
              ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'],
                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
              ])
              ->floatingToolbars([
                'paragraph' => [
                  'bold',
                  'italic',
                  'underline',
                  'strike',
                  'subscript',
                  'superscript',
                ],
              ])
              ->columnSpanFull()
              ->required()
              ->hint('Use placeholders enclosed in curly braces, e.g., {fullname}'),

            KeyValue::make('placeholders')
              ->columnSpanFull()
              ->required()
              ->default(null)
          ])
      ])
      ->action(function (array $data, Action $action) {
        $placeholders = $data['placeholders'] ?? [];
        $message = $data['message'];
        
        foreach ($placeholders as $key => $value) {
          $message = str_replace('{' . $key . '}', $value, $message);
        }
        $data['message'] = $message;

        Email::create($data);

        $action->success();
        $action->successNotificationTitle('Email from template successfully created');
      });
  }
}
