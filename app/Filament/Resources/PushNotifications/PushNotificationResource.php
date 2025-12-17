<?php

namespace App\Filament\Resources\PushNotifications;

use App\Filament\Resources\PushNotifications\Actions\ActionPushNotifications;
use App\Filament\Resources\PushNotifications\Pages\ManagePushNotifications;
use App\Models\PushNotification;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use UnitEnum;
use BackedEnum;

class PushNotificationResource extends Resource
{
  protected static ?string $model = PushNotification::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

  protected static string|UnitEnum|null $navigationGroup = 'Logs';

  protected static ?int $navigationSort = 9;

  protected static ?string $recordTitleAttribute = 'title';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        Select::make('user_id')
          ->label('User')
          ->relationship('user', 'name', fn ($query) => $query->where('has_allow_notification', true))
          ->searchable()
          ->preload()
          ->required()
          ->native(false)
          ->live()
          ->afterStateUpdated(function ($state, callable $set) {
            $user = $state ? User::find($state) : null;
            $set('token', $user?->notification_token ?? '');
          }),
          
        TextInput::make('token')
          ->label('Device Token')
          ->maxLength(255)
          ->copyable()
          ->readOnly(),

        TextInput::make('title')
          ->label('Notification Title')
          ->required()
          ->maxLength(255)
          ->columnSpanFull(),

        Textarea::make('body')
          ->label('Message Body')
          ->required()
          ->rows(4)
          ->columnSpanFull(),

        KeyValue::make('data')
          ->label('Additional Data')
          ->columnSpanFull(),
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          TextEntry::make('user.name')
            ->label('Recipient')
            ->placeholder('No user assigned'),

          TextEntry::make('title')
            ->label('Title'),

          TextEntry::make('token')
            ->label('Device Token')
            ->copyable()
            ->placeholder('Not set'),

          TextEntry::make('body')
            ->label('Message')
            ->wrap()
            ->columnSpan(2),

          TextEntry::make('error_message')
            ->label('Error Message')
            ->placeholder('No errors')
            ->color('danger')
        ])
          ->description('Notification content and recipient')
          ->columns(3),

        Section::make([
          KeyValueEntry::make('data')
            ->label('Additional Data')
            ->hiddenLabel()
            ->hidden(fn ($state) => !$state),

          KeyValueEntry::make('response_data')
            ->label('Response Data')
            ->hiddenLabel()
            ->hidden(fn ($state) => !$state),
        ])
          ->description('System data and response information')
          ->collapsible(),

        Section::make([
          TextEntry::make('sent_at')
            ->label('Sent At')
            ->dateTime()
            ->placeholder('Not sent yet')
            ->sinceTooltip(),

          TextEntry::make('created_at')
            ->label('Created')
            ->dateTime('M d, Y H:i'),

          TextEntry::make('updated_at')
            ->label('Last Updated')
            ->dateTime('M d, Y H:i')
            ->sinceTooltip(),
        ])
          ->description('System timestamps')
          ->columns(3)
          ->collapsible(),
      ])
        ->columns(1);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('title')
      ->columns([
        TextColumn::make('index')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('user.name')
          ->label('User')
          ->searchable()
          ->sortable(),
        TextColumn::make('title')
          ->label('Title')
          ->searchable()
          ->limit(50),
        TextColumn::make('body')
          ->label('Message')
          ->limit(30)
          ->toggleable(),
        IconColumn::make('sent_at')
          ->label('Status')
          ->boolean()
          ->getStateUsing(fn ($record) => $record->sent_at !== null)
          ->trueIcon('heroicon-o-check-circle')
          ->falseIcon('heroicon-o-clock')
          ->trueColor('success')
          ->falseColor('warning'),
        TextColumn::make('sent_at')
          ->label('Sent At')
          ->dateTime()
          ->sortable()
          ->placeholder('Not sent yet')
          ->toggleable(),
        TextColumn::make('error_message')
          ->label('Error')
          ->limit(20)
          ->color('danger')
          ->placeholder('No errors')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->label('Created')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->label('Updated')
          ->dateTime()
          ->sortable()
          ->toggleable(),
        TextColumn::make('deleted_at')
          ->label('Deleted')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('user')
          ->relationship('user', 'name')
          ->searchable()
          ->preload()
          ->native(false),
        TrashedFilter::make()
          ->native(false),
      ])
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View Push Notification Details')
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge),

          EditAction::make()
            ->modalWidth(Width::FiveExtraLarge)
            ->visible(fn (PushNotification $record): bool => !$record->sent_at),

          Action::make('sendNotification')
            ->label('Send Push Notification')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Send Push Notification')
            ->modalDescription('Send the push notification to the user immediately. Continue?')
            ->action(fn (PushNotification $record) => ActionPushNotifications::sendPushNotification($record))
            ->visible(fn (PushNotification $record): bool => !$record->sent_at),

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
      'index' => ManagePushNotifications::route('/'),
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
