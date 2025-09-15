<?php

namespace App\Filament\Resources\ActivityLogs;

use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Str;
use UnitEnum;
use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Models\ActivityLog;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActivityLogResource extends Resource
{
  protected static ?string $model = ActivityLog::class;

  protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

  protected static ?string $recordTitleAttribute = 'description';

  protected static string | UnitEnum | null $navigationGroup = 'Logs';

  protected static ?int $navigationSort = 10;

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        // ! Do something
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          TextEntry::make('causer.name')
            ->label('Causer'),

          TextEntry::make('subject_type')
            ->label('Subject')
            ->formatStateUsing(function ($state, Model $record) {
              if (!$state) return '-';
              return Str::of($state)->afterLast('\\')->headline() . ' # ' . $record->subject_id;
            }),

          TextEntry::make('created_at')
            ->dateTime()
            ->sinceTooltip(),

          TextEntry::make('log_name')
            ->label('Group')
            ->badge()
            ->formatStateUsing(fn($state) => ucwords($state)),
          
          TextEntry::make('event')
            ->label('Event')
            ->badge()
            ->color(fn ($state) => self::getEventColor($state)),

          TextEntry::make('description')
            ->label('Description')
            ->wrap()
            ->limit(300)
            ->columnSpanFull(),
        ])
          ->description('General information')
          ->collapsible()
          ->columns(3),

        Section::make([
          KeyValueEntry::make('prev_properties')
            ->label('Previous properties')
            ->hidden(fn ($state) => !$state),

          KeyValueEntry::make('properties')
            ->label('Properties')
            ->hidden(fn ($state) => !$state),
        ])
          ->description('Properties information')
          ->collapsible()
          ->visible(fn (ActivityLog $record): bool =>
            $record->properties->isNotEmpty() ||
            $record->prev_properties->isNotEmpty()
          )
      ])
        ->columns(1);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('description')
      ->columns([
        TextColumn::make('#')
          ->label('#')
          ->rowIndex(),
        TextColumn::make('log_name')
          ->label('Group')
          ->badge()
          ->formatStateUsing(fn($state) => ucwords($state))
          ->toggleable(),
        TextColumn::make('event')
          ->label('Event')
          ->badge()
          ->color(fn ($state) => self::getEventColor($state))
          ->toggleable(),
        TextColumn::make('description')
          ->label('Description')
          ->toggleable()
          ->wrap()
          ->limit(80)
          ->searchable(),
        TextColumn::make('subject_id')
          ->label('Subject')
          ->formatStateUsing(function ($state, Model $record) {
            if (!$state) return '-';
            return Str::of($record->subject_type)->afterLast('\\')->headline() . ' # ' . $state;
          })
          ->toggleable()
          ->searchable(),
        TextColumn::make('causer.name')
          ->label('Causer')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('batch_uuid')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(),
      ])
      ->filters([
        // TrashedFilter::make(),
      ])
      ->defaultSort('updated_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalHeading('View detail activity log')
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge),
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          // ! Do Something
        ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => ManageActivityLogs::route('/'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }

  public static function getEventColor(string $event): string
  {
    $colors = [
      'Updated'       => 'info',
      'Created'       => 'success',
      'Deleted'       => 'danger',
      'Force Deleted' => 'danger',
      'Restored'      => 'warning',
    ];

    return $colors[$event] ?? 'primary';
  }
}
