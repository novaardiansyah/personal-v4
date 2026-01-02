<?php

namespace App\Filament\Resources\Emails;

use App\Enums\EmailStatus;
use App\Filament\Resources\Emails\Pages\ManageEmails;
use App\Mail\EmailResource\DefaultMail;
use App\Models\Email;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class EmailResource extends Resource
{
  protected static ?string $model = Email::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;
  protected static ?string $recordTitleAttribute = 'subject';
  protected static string|UnitEnum|null $navigationGroup = 'Productivity';
  protected static ?int $navigationSort = 39;

  public static function getRelations(): array
  {
    return [
      RelationManagers\FilesRelationManager::class,
    ];
  }

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->default(null),
        TextInput::make('email')
          ->label('Email address')
          ->email()
          ->required(),
        TextInput::make('subject')
          ->default(null)
          ->required(),
        Select::make('status')
          ->options(EmailStatus::class)
          ->default('draft')
          ->required()
          ->native(false),
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
          ->required(),
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('')
          ->description('Email information')
          ->components([
            TextEntry::make('name'),
            TextEntry::make('email')
              ->label('Email address'),
            TextEntry::make('status')
              ->badge()
              ->color(fn(Email $record): string => $record->status->color())
              ->state(fn(Email $record): string => $record->status->label()),
            TextEntry::make('subject'),
            TextEntry::make('message')
              ->label('Message')
              ->html()
              ->columnSpan(2),
          ])
          ->columns(3),

        Section::make('')
          ->description('Timestamp information')
          ->components([
            TextEntry::make('created_at')
              ->sinceTooltip()
              ->dateTime(),
            TextEntry::make('updated_at')
              ->sinceTooltip()
              ->dateTime(),
            TextEntry::make('deleted_at')
              ->sinceTooltip()
              ->dateTime(),
          ])
          ->columns(3),
      ])
      ->columns(1);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('subject')
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(0)
          ->sortable(),
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('email')
          ->label('Email address')
          ->searchable()
          ->toggleable(),
        TextColumn::make('subject')
          ->searchable()
          ->toggleable(),
        TextColumn::make('status')
          ->badge()
          ->color(fn(Email $record): string => $record->status->color())
          ->state(fn(Email $record): string => $record->status->label())
          ->toggleable()
          ->sortable(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(),
      ])
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),

          EditAction::make(),

          Action::make('send')
            ->action(function (Email $record, Action $action) {
              $data = [
                'name' => $record->name ?? explode('@', $record->email)[0],
                'subject' => $record->subject,
                'message' => $record->message,
                'attachments' => $record->attachments,
              ];

              Mail::to($record->email)->queue(new DefaultMail($data));

              $record->update([
                'status' => EmailStatus::Sent,
              ]);

              $action->success();
              $action->successNotificationTitle('Email will be sent in the background');
            })
            ->label('Send')
            ->icon('heroicon-s-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Send Email')
            ->modalDescription('Are you sure you want to send this email?')
            ->visible(fn(Email $record): bool => $record->status === EmailStatus::Draft),

          ReplicateAction::make('replicate')
            ->label('Replicate')
            ->icon('heroicon-s-document-duplicate')
            ->color('warning')
            ->action(function (Email $record, Action $action) {
              $newRecord = $record->replicate();
              $newRecord->status = EmailStatus::Draft;
              $newRecord->subject = $record->subject . ' (Copy)';

              $newRecord->save();

              $action->success();
              $action->successNotificationTitle('Email replicated successfully');
            })
            ->requiresConfirmation()
            ->modalHeading('Replicate Email')
            ->modalDescription('Are you sure you want to replicate this email?'),

          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          ForceDeleteBulkAction::make(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => ManageEmails::route('/'),
      'view'  => Pages\ViewEmail::route('/{record}'),
      'edit'  => Pages\EditEmail::route('/{record}/edit'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }
}
