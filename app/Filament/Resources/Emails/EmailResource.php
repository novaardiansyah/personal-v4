<?php

namespace App\Filament\Resources\Emails;

use App\Filament\Resources\Emails\Pages\CreateWithTemplateEmail;
use BackedEnum;
use UnitEnum;
use App\Enums\EmailStatus;
use App\Filament\Resources\Emails\Pages\ManageEmails;
use App\Filament\Resources\Emails\Pages\ActionEmail;
use App\Filament\Resources\Emails\Pages\CreateEmail;
use App\Filament\Resources\Emails\Pages\EditEmail;
use App\Filament\Resources\Emails\Pages\ViewEmail;
use App\Filament\Resources\Emails\RelationManagers\FilesRelationManager;
use App\Models\Email;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class EmailResource extends Resource
{
  protected static ?string $model = Email::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;
  protected static ?string $recordTitleAttribute = 'subject';
  protected static string|UnitEnum|null $navigationGroup = 'Emails';
  protected static ?int $navigationSort = 20;

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Grid::make(1)
          ->columnSpan(2)
          ->schema([
            Section::make()
              ->description('General information')
              ->collapsible()
              ->schema([
                TextInput::make('email')
                  ->label('Email address')
                  ->email()
                  ->required()
                  ->live(onBlur: true)
                  ->afterStateUpdated(function (?string $state, callable $set) {
                    if ($state && str_contains($state, '@')) {
                      $set('name', explode('@', $state)[0]);
                    }
                  }),
                TextInput::make('name')
                  ->default(null),
              ]),

            Section::make()
              ->description('Content information')
              ->collapsible()
              ->schema([
                TextInput::make('subject')
                  ->default(null)
                  ->required(),

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
              ]),
          ]),

        Section::make()
          ->description('Settings information')
          ->collapsible()
          ->schema([
            Select::make('status')
              ->options(EmailStatus::class)
              ->default('draft')
              ->required()
              ->native(false)
              ->disabledOn('edit'),
            TextInput::make('url_attachment')
              ->label('Attachment URL')
              ->default(null)
              ->columnSpan(1),

            Grid::make(2)
              ->schema([
                Toggle::make('has_header')
                  ->label('Has Header')
                  ->default(false),
                Toggle::make('has_footer')
                  ->label('Has Footer')
                  ->default(false),
              ]),
          ]),
      ])->columns(3);
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
						IconEntry::make('has_header')
							->label('Header')
							->boolean(),
						IconEntry::make('has_footer')
							->label('Footer')
							->boolean(),
            TextEntry::make('message')
              ->label('Message')
              ->html()
              ->columnSpanFull(),
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
          ->wrap()
          ->limit(120)
          ->toggleable(),
        TextColumn::make('status')
          ->badge()
          ->color(fn(Email $record): string => $record->status->color())
          ->state(fn(Email $record): string => $record->status->label())
          ->toggleable()
          ->sortable(),
        TextColumn::make('url_attachment')
          ->label('URL Attachment')
          ->copyable()
          ->tooltip('Copy URL Attachment')
          ->toggleable(isToggledHiddenByDefault: true),
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

          ActionEmail::send(),
          ActionEmail::replicate(),
          ActionEmail::preview(),

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
      'create' => CreateEmail::route('/create'),
      'create-with-template' => CreateWithTemplateEmail::route('/create-with-template'),
      'view' => ViewEmail::route('/{record}'),
      'edit' => EditEmail::route('/{record}/edit'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }

  public static function getRelations(): array
  {
    return [
      FilesRelationManager::class,
    ];
  }
}
