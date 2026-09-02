<?php

namespace App\Filament\Resources\DiscordMessages;

use App\Filament\Resources\DiscordMessages\Pages\CreateDiscordMessage;
use App\Filament\Resources\DiscordMessages\Pages\EditDiscordMessage;
use App\Filament\Resources\DiscordMessages\Pages\ListDiscordMessages;
use App\Filament\Resources\DiscordMessages\Pages\ViewDiscordMessage;
use App\Filament\Resources\DiscordMessages\Schemas\DiscordMessageForm;
use App\Filament\Resources\DiscordMessages\Schemas\DiscordMessageInfolist;
use App\Filament\Resources\DiscordMessages\Tables\DiscordMessagesTable;
use App\Models\DiscordMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DiscordMessageResource extends Resource
{
  protected static ?string $model = DiscordMessage::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

  protected static string|UnitEnum|null $navigationGroup = 'Discord';

  protected static ?int $navigationSort = 4;

  protected static ?string $navigationLabel = 'Messages';

  protected static ?string $recordTitleAttribute = 'content';

  public static function form(Schema $schema): Schema
  {
    return DiscordMessageForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return DiscordMessageInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return DiscordMessagesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListDiscordMessages::route('/'),
      'create' => CreateDiscordMessage::route('/create'),
      'view'   => ViewDiscordMessage::route('/{record}'),
      'edit'   => EditDiscordMessage::route('/{record}/edit'),
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
