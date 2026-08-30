<?php

namespace App\Filament\Resources\DiscordServers;

use App\Filament\Resources\DiscordServers\Pages\CreateDiscordServer;
use App\Filament\Resources\DiscordServers\Pages\EditDiscordServer;
use App\Filament\Resources\DiscordServers\Pages\ListDiscordServers;
use App\Filament\Resources\DiscordServers\Pages\ViewDiscordServer;
use App\Filament\Resources\DiscordServers\RelationManagers\ChannelsRelationManager;
use App\Filament\Resources\DiscordServers\Schemas\DiscordServerForm;
use App\Filament\Resources\DiscordServers\Schemas\DiscordServerInfolist;
use App\Filament\Resources\DiscordServers\Tables\DiscordServersTable;
use App\Models\DiscordServer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DiscordServerResource extends Resource
{
  protected static ?string $model = DiscordServer::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

  protected static string|UnitEnum|null $navigationGroup = 'Discord';

  protected static ?int $navigationSort = 1;

  protected static ?string $navigationLabel = 'Servers';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return DiscordServerForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return DiscordServerInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return DiscordServersTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      ChannelsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListDiscordServers::route('/'),
      'create' => CreateDiscordServer::route('/create'),
      'view'   => ViewDiscordServer::route('/{record}'),
      'edit'   => EditDiscordServer::route('/{record}/edit'),
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
