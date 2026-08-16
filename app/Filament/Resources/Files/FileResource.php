<?php

namespace App\Filament\Resources\Files;

use App\Filament\Resources\Files\Pages\CreateFile;
use App\Filament\Resources\Files\Pages\EditFile;
use App\Filament\Resources\Files\Pages\ListFiles;
use App\Filament\Resources\Files\Pages\ViewFile;
use App\Filament\Resources\Files\Schemas\FileForm;
use App\Filament\Resources\Files\Schemas\FileInfolist;
use App\Filament\Resources\Files\Tables\FilesTable;
use App\Models\File;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FileResource extends Resource
{
  protected static ?string $model = File::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

  protected static string|UnitEnum|null $navigationGroup = 'File Manager';

  protected static ?int $navigationSort = 20;

  protected static ?string $recordTitleAttribute = 'file_name';

  public static function form(Schema $schema): Schema
  {
    return FileForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return FileInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return FilesTable::configure($table);
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
      'index'  => ListFiles::route('/'),
      'create' => CreateFile::route('/create'),
      'view'   => ViewFile::route('/{record}'),
      'edit'   => EditFile::route('/{record}/edit'),
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
