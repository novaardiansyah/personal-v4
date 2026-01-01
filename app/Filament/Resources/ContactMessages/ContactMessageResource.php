<?php

namespace App\Filament\Resources\ContactMessages;

use BackedEnum;
use UnitEnum;

use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactMessageResource extends Resource
{
  protected static ?string $model = ContactMessage::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

  protected static string|UnitEnum|null $navigationGroup = 'Productivity';

  protected static ?string $recordTitleAttribute = 'subject';

  protected static ?int $navigationSort = 40;

  protected static ?string $modelLabel = 'Contact Form';

  protected static ?string $pluralModelLabel = 'Contact Forms';

  public static function getNavigationBadge(): ?string
  {
    return static::getModel()::where('is_read', false)->count();
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
          ->default(null),
        TextInput::make('subject')
          ->default(null),
        Textarea::make('message')
          ->default(null)
          ->columnSpanFull(),
        Toggle::make('is_read')
          ->required(),
        TextInput::make('ip_address')
          ->default(null),
        TextInput::make('user_agent')
          ->default(null),
        TextInput::make('path')
          ->default(null),
        TextInput::make('url')
          ->default(null),
        TextInput::make('full_url')
          ->default(null),
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Message Information')
          ->schema([
            Grid::make(2)
              ->schema([
                TextEntry::make('name')
                  ->label('Name'),
                TextEntry::make('email')
                  ->label('Email Address'),
              ]),

            TextEntry::make('subject')
              ->label('Subject')
              ->columnSpanFull(),

            TextEntry::make('message')
              ->label('Message')
              ->columnSpanFull()
              ->markdown()
              ->prose(),
          ]),

        Section::make('Status & Metadata')
          ->schema([
            Grid::make(3)
              ->schema([
                IconEntry::make('is_read')
                  ->label('Read Status')
                  ->boolean(),
                TextEntry::make('ip_address')
                  ->label('IP Address'),
                TextEntry::make('created_at')
                  ->label('Received')
                  ->dateTime('M d, Y H:i')
                  ->sinceTooltip(),
              ]),
          ]),

        Section::make('Technical Details')
          ->schema([
            Grid::make(3)
              ->schema([
                TextEntry::make('user_agent')
                  ->label('User Agent')
                  ->limit(50)
                  ->tooltip(fn($record): string => $record->user_agent),
                TextEntry::make('path')
                  ->label('Path'),
                TextEntry::make('url')
                  ->label('URL')
                  ->limit(50)
                  ->tooltip(fn($record): string => $record->url),
              ]),
          ])
          ->collapsed(),
      ])
      ->columns(1);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('subject')
      ->columns([
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('email')
          ->label('Email address')
          ->searchable()
          ->toggleable(),
        TextColumn::make('subject')
          ->searchable()
          ->toggleable()
          ->wrap()
          ->limit(100),
        TextColumn::make('message')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true)
          ->wrap()
          ->limit(200),
        IconColumn::make('is_read')
          ->boolean()
          ->toggleable(),
        TextColumn::make('ip_address')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('user_agent')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true)
          ->limit(50),
        TextColumn::make('path')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('url')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true)
          ->limit(50),
        TextColumn::make('full_url')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true)
          ->limit(50),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->defaultSort('updated_at', 'desc')
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View Message')
            ->modalWidth(Width::FiveExtraLarge),

          Action::make('read')
            ->action(function (ContactMessage $record, Action $action) {
              $record->update([
                'is_read' => true,
              ]);

              $action->success();
              $action->successNotificationTitle('Message marked as read');
            })
            ->label('Mark as Read')
            ->icon('heroicon-s-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Mark as Read')
            ->modalDescription('Are you sure you want to mark this message as read?')
            ->visible(fn(ContactMessage $record): bool => $record->is_read === false),

          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ]),
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
      'index' => ManageContactMessages::route('/'),
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
