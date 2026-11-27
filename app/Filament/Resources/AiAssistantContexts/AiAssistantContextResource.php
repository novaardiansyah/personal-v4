<?php

namespace App\Filament\Resources\AiAssistantContexts;

use App\Filament\Resources\AiAssistantContexts\Pages\CreateAiAssistantContext;
use App\Filament\Resources\AiAssistantContexts\Pages\EditAiAssistantContext;
use App\Filament\Resources\AiAssistantContexts\Pages\ListAiAssistantContexts;
use App\Filament\Resources\AiAssistantContexts\Schemas\AiAssistantContextForm;
use App\Filament\Resources\AiAssistantContexts\Tables\AiAssistantContextsTable;
use App\Models\AiAssistantContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AiAssistantContextResource extends Resource
{
  protected static ?string $model = AiAssistantContext::class;

  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCpuChip;

  protected static string | UnitEnum | null $navigationGroup = 'Productivity';

  protected static ?int $navigationSort = 2;

  protected static ?string $navigationLabel = 'AI Personas';

  protected static ?string $recordTitleAttribute = 'name';

  public static function form(Schema $schema): Schema
  {
    return AiAssistantContextForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return AiAssistantContextsTable::configure($table);
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListAiAssistantContexts::route('/'),
      'create' => CreateAiAssistantContext::route('/create'),
      'edit'   => EditAiAssistantContext::route('/{record}/edit'),
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
