<?php

namespace App\Filament\Resources\Emails\RelationManagers;

use App\Filament\Resources\Emails\Pages\ActionEmail;
use App\Filament\Resources\Files\FileResource;
use Filament\Actions\CreateAction;
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
        ActionEmail::uploadAttachment(),
      ]);
  }
}
