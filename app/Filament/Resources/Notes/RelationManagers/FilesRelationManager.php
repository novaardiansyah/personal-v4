<?php

namespace App\Filament\Resources\Notes\RelationManagers;

use App\Filament\Resources\Files\FileResource;
use App\Filament\Resources\Notes\Schemas\NoteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FilesRelationManager extends RelationManager
{
  protected static string $relationship = 'files';

  protected static ?string $relatedResource = FileResource::class;

  public function table(Table $table): Table
  {
    return $table
      ->headerActions([
        NoteAction::uploadAttachment(),
      ]);
  }
}
