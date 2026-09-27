<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use App\Filament\Resources\Files\FileResource;
use App\Filament\Resources\Payments\Actions\PaymentAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FilesRelationManager extends RelationManager
{
  protected static string $relationship = 'files';

  protected static ?string $relatedResource = FileResource::class;

  public function table(Table $table): Table
  {
    return FileResource::table($table)
      ->headerActions([
        PaymentAction::uploadAttachment(),
      ]);
  }
}
