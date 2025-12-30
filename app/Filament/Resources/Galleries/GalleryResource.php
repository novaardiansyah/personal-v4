<?php

namespace App\Filament\Resources\Galleries;

use App\Filament\Resources\Galleries\Pages\ManageGalleries;
use App\Models\Gallery;
use BackedEnum;
use Filament\Actions\Action;
use UnitEnum;
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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GalleryResource extends Resource
{
  protected static ?string $model = Gallery::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;
  protected static string|UnitEnum|null $navigationGroup = 'Productivity';
  protected static ?int $navigationSort = 10;
  protected static ?string $recordTitleAttribute = 'file_name';

  public static function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        FileUpload::make('file_path')
          ->image()
          ->directory('images/gallery')
          ->disk('public')
          ->maxSize(5120)
          ->imageEditor()
          ->columnSpanFull(),
        Textarea::make('description')
          ->default(null)
          ->rows(3)
          ->columnSpanFull(),
        Grid::make(4)
          ->schema([
            Toggle::make('is_private')
              ->default(false),
            Toggle::make('has_optimized')
              ->default(true)
              ->disabledOn('edit'),
          ]),
      ])
      ->columns(1);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('file_name')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        ImageColumn::make('file_path')
          ->label('Preview')
          ->circular()
          ->state(fn ($record): string => config('services.self.cdn_url') . '/' . $record->file_path),
        TextColumn::make('file_name')
          ->searchable()
          ->wrap()
          ->limit(50)
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('file_size')
          ->formatStateUsing(fn($state) => number_format($state / 1024, 2) . ' KB')
          ->sortable(),
        TextColumn::make('subject_id')
          ->label('Subject')
          ->formatStateUsing(function ($state, Model $record) {
            if (!$state)
              return '-';
            return Str::of($record->subject_type)->afterLast('\\')->headline() . ' # ' . $state;
          })
          ->toggleable()
          ->searchable(),
        TextColumn::make('description')
          ->searchable()
          ->wrap()
          ->limit(100)
          ->toggleable(isToggledHiddenByDefault: true),
        IconColumn::make('is_private')
          ->boolean(),
        IconColumn::make('has_optimized')
          ->boolean(),
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
          ViewAction::make()
            ->modalHeading('View Gallery')
            ->modalWidth(Width::FourExtraLarge)
            ->slideOver(),

          DeleteAction::make()
            ->action(function (Gallery $record, Action $action) {
              $cdnUrl = config('services.self.cdn_api_url') . '/galleries/' . $record->id;
              $cdnKey = config('services.self.cdn_api_key');

              $response = Http::withToken($cdnKey)
                ->delete($cdnUrl);

              if ($response->successful()) {
                $action->success();
                $action->successNotificationTitle('Image deleted successfully');
              } else {
                $action->failure();
                $action->failureNotificationTitle('Failed to delete image');
              }
            }),

          ForceDeleteAction::make()
            ->action(function (Gallery $record, Action $action) {
              $cdnUrl = config('services.self.cdn_api_url') . '/galleries/' . $record->id . '/force';
              $cdnKey = config('services.self.cdn_api_key');

              $response = Http::withToken($cdnKey)
                ->delete($cdnUrl);

              if ($response->successful()) {
                $action->success();
                $action->successNotificationTitle('Image deleted successfully');
              } else {
                $action->failure();
                $action->failureNotificationTitle('Failed to delete image');
              }
            }),

          RestoreAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make()
            ->action(function (Collection $records) {
              $cdnKey = config('services.self.cdn_api_key');
              $cdnBaseUrl = config('services.self.cdn_api_url') . '/galleries/';

              foreach ($records as $record) {
                Http::withToken($cdnKey)->delete($cdnBaseUrl . $record->id);
              }

              Notification::make()
                ->title('Images deleted successfully')
                ->success()
                ->send();
            }),

          ForceDeleteBulkAction::make()
            ->action(function (Collection $records) {
              $cdnKey = config('services.self.cdn_api_key');
              $cdnBaseUrl = config('services.self.cdn_api_url') . '/galleries/';

              foreach ($records as $record) {
                Http::withToken($cdnKey)->delete($cdnBaseUrl . $record->id . '/force');
              }

              Notification::make()
                ->title('Images deleted successfully')
                ->success()
                ->send();
            }),
            
          RestoreBulkAction::make(),
        ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => ManageGalleries::route('/'),
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
