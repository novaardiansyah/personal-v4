<?php

namespace App\Filament\Resources\Galleries;

use BackedEnum;
use UnitEnum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Galleries\Pages\ManageGalleries;
use App\Models\Gallery;
use App\Services\GalleryResource\CdnService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
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
        // ! Form is handled by CdnService
      ]);
  }

  public static function infolist(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make([
          TextEntry::make('file_name')
            ->copyable()
            ->columnSpan(2),

          TextEntry::make('file_size')
            ->formatStateUsing(fn($state) => number_format($state / 1024, 2) . ' KB'),

          TextEntry::make('subject_type')
            ->label('Subject')
            ->formatStateUsing(function ($state, Model $record) {
              if (!$state)
                return '-';
              return Str::of($state)->afterLast('\\')->headline() . ' # ' . $record->subject_id;
            }),

          TextEntry::make('description')
            ->markdown()
            ->prose()
            ->columnSpanFull(),
        ])
          ->description('File information')
          ->collapsible()
          ->columns(4),

        Section::make([
          TextEntry::make('file_path')
            ->copyable(),

          ImageEntry::make('file_path')
            ->columnSpanFull()
            ->width('60%')
            ->height('auto')
            ->label('Preview')
            ->state(fn ($record): string => config('services.self.cdn_url') . '/' . $record->file_path),
        ])
          ->description('Image preview')
          ->collapsible(),

        Section::make([
          IconEntry::make('is_private')
            ->boolean(),
          IconEntry::make('has_optimized')
            ->boolean(),
        ])
          ->description('Status')
          ->collapsible()
          ->columns(2),

        Section::make([
          TextEntry::make('created_at')
            ->dateTime()
            ->sinceTooltip(),
          TextEntry::make('updated_at')
            ->dateTime()
            ->sinceTooltip(),
          TextEntry::make('deleted_at')
            ->dateTime(),
        ])
          ->description('Timestamps')
          ->collapsible()
          ->columns(3),
      ])
      ->columns(1);
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
          ->state(fn($record): string => config('services.self.cdn_url') . '/' . $record->file_path),
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
              $response = app(CdnService::class)->delete($record->id);

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
              $response = app(CdnService::class)->forceDelete($record->id);

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
              $cdnService = app(CdnService::class);

              foreach ($records as $record) {
                $cdnService->delete($record->id);
              }

              Notification::make()
                ->title('Images deleted successfully')
                ->success()
                ->send();
            }),

          ForceDeleteBulkAction::make()
            ->action(function (Collection $records) {
              $cdnService = app(CdnService::class);

              foreach ($records as $record) {
                $cdnService->forceDelete($record->id);
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
