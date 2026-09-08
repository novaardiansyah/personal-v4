<?php

namespace App\Filament\Resources\DiscordChannels;

use App\Filament\Resources\DiscordChannels\Pages\CreateDiscordChannel;
use App\Filament\Resources\DiscordChannels\Pages\EditDiscordChannel;
use App\Filament\Resources\DiscordChannels\Pages\ListDiscordChannels;
use App\Filament\Resources\DiscordChannels\Pages\ViewDiscordChannel;
use App\Filament\Resources\DiscordChannels\RelationManagers\WebhooksRelationManager;
use App\Filament\Resources\DiscordChannels\Schemas\DiscordChannelForm;
use App\Filament\Resources\DiscordChannels\Schemas\DiscordChannelInfolist;
use App\Filament\Resources\DiscordChannels\Tables\DiscordChannelsTable;
use App\Models\DiscordChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DiscordChannelResource extends Resource
{
  protected static ?string $model = DiscordChannel::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

  protected static string|UnitEnum|null $navigationGroup = 'Discord';

  protected static ?int $navigationSort = 2;

  protected static ?string $navigationLabel = 'Channels';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return DiscordChannelForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return DiscordChannelInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return DiscordChannelsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      WebhooksRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListDiscordChannels::route('/'),
      'create' => CreateDiscordChannel::route('/create'),
      'view'   => ViewDiscordChannel::route('/{record}'),
      'edit'   => EditDiscordChannel::route('/{record}/edit'),
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
