<?php

namespace App\Filament\Resources\Emails\Pages;

use App\Filament\Resources\Emails\EmailResource;
use App\Models\EmailTemplate;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CreateWithTemplateEmail extends CreateRecord
{
  protected static string $resource = EmailResource::class;

  protected static ?string $title = 'Create Email from Template';

  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
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
              ->getOptionLabelFromRecordUsing(function (EmailTemplate $record) {
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

                if (!$get('name'))
                  $set('name', explode('@', $state)[0]);
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
      ->columns(1);
  }

  protected function mutateFormDataBeforeCreate(array $data): array
  {
    $placeholders = $data['placeholders'] ?? [];
    $message = $data['message'];

    foreach ($placeholders as $key => $value) {
      $message = str_replace('{' . $key . '}', $value, $message);
    }
    $data['message'] = $message;

    unset($data['placeholders']);
    unset($data['email_template_id']);

    return $data;
  }

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('edit');
  }

  protected function getCreatedNotificationTitle(): ?string
  {
    return 'Email from template successfully created';
  }
}
