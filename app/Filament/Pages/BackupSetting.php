<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\BackupSettingForm;
use App\Filament\Resources\Settings\Actions\ChangeValueAction;
use App\Filament\Resources\Settings\Actions\ReplicateAction;
use App\Filament\Resources\Settings\Schemas\SettingInfolist;
use App\Models\Backup;
use App\Models\BackupJob;
use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use App\Models\BackupStorageProvider;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class BackupSetting extends Page implements HasTable
{
  use InteractsWithTable;

  protected static string|BackedEnum|null $navigationIcon  = Heroicon::OutlinedCog6Tooth;
  protected static string|UnitEnum|null   $navigationGroup = 'Backup';
  protected static ?int                   $navigationSort  = 6;
  protected static ?string                $navigationLabel = 'Settings';
  protected static ?string                $title           = 'Backup Setting';
  protected static ?string                $slug            = 'backup-settings';

  protected string $view = 'filament.pages.backup-setting';

  public function getTitle(): string
  {
    return 'Backup Setting';
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Setting::query()
          ->where(function (Builder $query) {
            $query->whereIn('subject_type', [
              Backup::class,
              BackupSchedule::class,
              BackupJob::class,
              BackupStorage::class,
              BackupStorageProvider::class,
            ])
            ->orWhere('subject_type', 'like', '%Backup%');
          })
      )
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        TextColumn::make('name')
          ->searchable()
          ->toggleable(),
        TextColumn::make('key')
          ->label('Alias')
          ->badge()
          ->searchable()
          ->copyable()
          ->toggleable(),
        TextColumn::make('value')
          ->searchable()
          ->toggleable()
          ->badge(),
        TextColumn::make('subject_id')
          ->label('Subject')
          ->formatStateUsing(function ($state, Setting $record) {
            if (!$record->subject_type) {
              return '-';
            }
            $subjectName = Str::of($record->subject_type)->afterLast('\\')->headline();
            return $state ? "{$subjectName} # {$state}" : (string) $subjectName;
          })
          ->toggleable()
          ->searchable(),
        TextColumn::make('description')
          ->label('Description')
          ->limit(50)
          ->searchable()
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
        TrashedFilter::make(),
      ])
      ->headerActions([
        CreateAction::make()
          ->model(Setting::class)
          ->modalWidth(Width::FiveExtraLarge)
          ->schema(fn(Schema $schema) => BackupSettingForm::configure($schema))
          ->mutateDataUsing(function (array $data): array {
            if ($data['has_options'] ?? false) {
              $data['value'] = $data['value_option'] ?? $data['value'];
            }
            return $data;
          }),
      ])
      ->recordAction('change_value')
      ->recordUrl(null)
      ->recordActions([
        ActionGroup::make([
          ViewAction::make()
            ->modalWidth(Width::Large)
            ->schema(fn(Schema $schema) => SettingInfolist::configure($schema)),

          EditAction::make()
            ->modalWidth(Width::Large)
            ->schema(fn(Schema $schema) => BackupSettingForm::configure($schema))
            ->mutateRecordDataUsing(function (array $data, Setting $record): array {
              if ($data['has_options'] ?? $record->has_options) {
                $data['value_option'] = $data['value'];
              }
              return $data;
            })
            ->mutateFormDataUsing(function (array $data, Setting $record): array {
              if ($data['has_options'] ?? $record->has_options) {
                $data['value'] = $data['value_option'] ?? $data['value'];
              }
              return $data;
            }),

          ChangeValueAction::make(),

          ReplicateAction::make(),

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
}
