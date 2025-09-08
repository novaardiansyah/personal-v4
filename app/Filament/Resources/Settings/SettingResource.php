<?php

namespace App\Filament\Resources\Settings;

use BackedEnum;
use UnitEnum;

use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\Pages\ViewSetting;
use App\Filament\Resources\Settings\Schemas\SettingForm;
use App\Filament\Resources\Settings\Schemas\SettingInfolist;
use App\Filament\Resources\Settings\Tables\SettingsTable;
use App\Models\Setting;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingResource extends Resource
{
  protected static ?string $model = Setting::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
  protected static string | UnitEnum | null $navigationGroup = 'Settings';
  protected static ?int $navigationSort = 30;

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return SettingForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return SettingInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return SettingsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListSettings::route('/'),
      'create' => CreateSetting::route('/create'),
      'view'   => ViewSetting::route('/{record}'),
      'edit'   => EditSetting::route('/{record}/edit'),
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
