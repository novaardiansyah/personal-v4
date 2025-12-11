<?php

namespace App\Filament\Resources\Skills;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\Skills\Pages\ManageSkills;
use App\Models\Skill;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SkillResource extends Resource
{
  protected static ?string $model = Skill::class;
  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedAcademicCap;
  protected static string | UnitEnum | null $navigationGroup = 'Web Content';
  protected static ?int $navigationSort = 30;
  protected static ?string $recordTitleAttribute = 'name';

  public static function shouldRegisterNavigation(): bool
  {
    return false;
  }

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->columns(1)
      ->components([
        TextInput::make('name')
          ->required()
          ->maxLength(255),
        TextInput::make('percentage')
          ->numeric()
          ->minValue(0)
          ->maxValue(100)
          ->default(0)
          ->suffix('%'),
        Toggle::make('is_active')
          ->required()
          ->default(false),
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextEntry::make('name'),
        TextEntry::make('percentage')
          ->suffix('%'),
        IconEntry::make('is_active')
          ->boolean(),

        TextEntry::make('created_at')
          ->dateTime(),
        TextEntry::make('updated_at')
          ->sinceTooltip()
          ->dateTime(),
        TextEntry::make('deleted_at')
          ->dateTime(),
      ])
      ->columns(3);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('percentage')
          ->numeric()
          ->suffix('%')
          ->toggleable(),
        IconColumn::make('is_active')
          ->boolean()
          ->toggleable(),
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
      ->defaultSort('updated_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make()
            ->modalWidth(Width::Medium),
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
      'index' => ManageSkills::route('/'),
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
