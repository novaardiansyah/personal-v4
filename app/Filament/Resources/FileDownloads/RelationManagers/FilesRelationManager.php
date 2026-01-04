<?php

namespace App\Filament\Resources\FileDownloads\RelationManagers;

use App\Filament\Resources\Files\FileResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;

class FilesRelationManager extends RelationManager
{
  protected static string $relationship = 'files';

  protected static ?string $relatedResource = FileResource::class;

  public function table(Table $table): Table
  {
    return FileResource::table($table)
      ->headerActions([
        AssociateAction::make()
          ->multiple()
          ->modalWidth(Width::TwoExtraLarge)
          ->preloadRecordSelect()
          ->modalHeading('Associate Files')
          ->modalDescription('Select the files you want to associate with this file download')
          ->recordSelectSearchColumns(['code', 'file_name'])
      ])
      ->recordActions([
        ActionGroup::make([
          DissociateAction::make()
        ])
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DissociateBulkAction::make()
        ]),
      ]);
  }
}
