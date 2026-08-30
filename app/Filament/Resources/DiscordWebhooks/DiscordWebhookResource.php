<?php

namespace App\Filament\Resources\DiscordWebhooks;

use App\Filament\Resources\DiscordWebhooks\Pages\CreateDiscordWebhook;
use App\Filament\Resources\DiscordWebhooks\Pages\EditDiscordWebhook;
use App\Filament\Resources\DiscordWebhooks\Pages\ListDiscordWebhooks;
use App\Filament\Resources\DiscordWebhooks\Pages\ViewDiscordWebhook;
use App\Filament\Resources\DiscordWebhooks\Schemas\DiscordWebhookForm;
use App\Filament\Resources\DiscordWebhooks\Schemas\DiscordWebhookInfolist;
use App\Filament\Resources\DiscordWebhooks\Tables\DiscordWebhooksTable;
use App\Models\DiscordWebhook;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DiscordWebhookResource extends Resource
{
  protected static ?string $model = DiscordWebhook::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

  protected static string|UnitEnum|null $navigationGroup = 'Discord';

  protected static ?int $navigationSort = 3;

  protected static ?string $navigationLabel = 'Webhooks';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return DiscordWebhookForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return DiscordWebhookInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return DiscordWebhooksTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListDiscordWebhooks::route('/'),
      'create' => CreateDiscordWebhook::route('/create'),
      'view'   => ViewDiscordWebhook::route('/{record}'),
      'edit'   => EditDiscordWebhook::route('/{record}/edit'),
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
