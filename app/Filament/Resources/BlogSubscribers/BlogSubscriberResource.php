<?php

namespace App\Filament\Resources\BlogSubscribers;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\BlogSubscribers\Pages\ManageBlogSubscribers;
use App\Models\BlogSubscriber;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BlogSubscriberResource extends Resource
{
  protected static ?string $model = BlogSubscriber::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;
  protected static string|UnitEnum|null $navigationGroup = 'Blog';
  protected static ?string $modelLabel = 'Subscriber';
  protected static ?string $pluralModelLabel = 'Subscribers';
  protected static ?int $navigationSort = 40;
  protected static ?string $recordTitleAttribute = 'email';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('email')
          ->email()
          ->required()
          ->unique(ignoreRecord: true)
          ->live(onBlur: true)
          ->afterStateUpdated(function (?string $state, Set $set) {
            if (!$state) return $set('name', null);
            $set('email', textLower($state));
            $set('name', explode('@', $state)[0]);
          }),
        TextInput::make('name')
          ->default(null),
        TextInput::make('token')
          ->required()
          ->default(fn() => Str::uuid7()->toString())
          ->disabled()
          ->dehydrated(),
        DateTimePicker::make('verified_at')
          ->label('Verified At')
          ->native(false),
        DateTimePicker::make('subscribed_at')
          ->label('Subscribed At')
          ->default(now())
          ->native(false),
        DateTimePicker::make('unsubscribed_at')
          ->label('Unsubscribed At')
          ->native(false),
      ])
      ->columns(2);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Subscriber Information')
          ->schema([
            TextEntry::make('email')
              ->label('Email')
              ->copyable()
              ->badge()
              ->color('info'),
            TextEntry::make('name')
              ->label('Name'),
            TextEntry::make('token')
              ->label('Token')
              ->copyable()
              ->badge()
              ->color('warning'),
          ])
          ->columns(3),

        Section::make()
          ->description('Subscription Status')
          ->schema([
            TextEntry::make('verified_at')
              ->label('Verified At')
              ->dateTime()
              ->placeholder('Not verified'),
            TextEntry::make('subscribed_at')
              ->label('Subscribed At')
              ->dateTime()
              ->placeholder('Not subscribed'),
            TextEntry::make('unsubscribed_at')
              ->label('Unsubscribed At')
              ->dateTime()
              ->placeholder('Still subscribed'),
          ])
          ->columns(3),

        Section::make()
          ->description('Timestamps')
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->label('Updated At')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime(),
          ])
          ->columns(3)
          ->collapsible(),
      ])
      ->columns(1);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('email')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('email')
          ->searchable()
          ->copyable(),
        TextColumn::make('name')
          ->searchable()
          ->wrap()
          ->limit(30),
        TextColumn::make('verified_at')
          ->label('Verified')
          ->dateTime()
          ->sortable()
          ->placeholder('Not verified'),
        TextColumn::make('subscribed_at')
          ->label('Subscribed')
          ->dateTime()
          ->sortable()
          ->placeholder('Not subscribed'),
        TextColumn::make('unsubscribed_at')
          ->label('Unsubscribed')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
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
      ->filters([
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('created_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalWidth(Width::FiveExtraLarge)
            ->slideOver(),

          EditAction::make(),
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
      'index' => ManageBlogSubscribers::route('/'),
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
