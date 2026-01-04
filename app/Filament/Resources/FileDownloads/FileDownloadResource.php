<?php

namespace App\Filament\Resources\FileDownloads;

use App\Filament\Resources\FileDownloads\Pages\CreateFileDownload;
use App\Filament\Resources\FileDownloads\Pages\EditFileDownload;
use App\Filament\Resources\FileDownloads\Pages\ListFileDownloads;
use App\Filament\Resources\FileDownloads\Pages\ViewFileDownload;
use App\Filament\Resources\FileDownloads\RelationManagers\FilesRelationManager;
use App\Filament\Resources\FileDownloads\Schemas\FileDownloadForm;
use App\Filament\Resources\FileDownloads\Schemas\FileDownloadInfolist;
use App\Filament\Resources\FileDownloads\Tables\FileDownloadsTable;
use App\Models\FileDownload;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FileDownloadResource extends Resource
{
  protected static ?string $model = FileDownload::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderArrowDown;
  protected static string|UnitEnum|null $navigationGroup = 'File Manager';
  protected static ?int $navigationSort = 30;
  protected static ?string $recordTitleAttribute = 'code';

  public static function form(Schema $schema): Schema
  {
    return FileDownloadForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return FileDownloadInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return FileDownloadsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      FilesRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListFileDownloads::route('/'),
      'create' => CreateFileDownload::route('/create'),
      'view' => ViewFileDownload::route('/{record}'),
      'edit' => EditFileDownload::route('/{record}/edit'),
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
