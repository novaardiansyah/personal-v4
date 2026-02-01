<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Illuminate\Support\Carbon;;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Basic email information')
          ->columnSpan(2)
          ->collapsible()
          ->schema([
            TextInput::make('subject')
              ->default(null)
              ->required()
              ->hint('Supports placeholders, e.g., {fullname}'),
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
              ->hint('Use placeholders enclosed in curly braces, e.g., {fullname}')
          ]),

        Section::make()
          ->description('Template information')
          ->collapsible()
          ->schema([
            TextInput::make('code')
              ->label('Template ID')
              ->disabled()
              ->placeholder(fn(): string => getCode('email_template', false)),
            KeyValue::make('placeholders')
              ->columnSpanFull()
              ->required()
              ->default([
                'created_at' => Carbon::now()->toDateTimeString(),
              ])
          ])
      ])
      ->columns(3);
  }
}
