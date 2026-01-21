<?php

namespace App\Filament\Resources\Notes\Schemas;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Files\Schemas\FileAction;

class NoteAction
{
  public static function uploadAttachment(): CreateAction
  {
    return FileAction::uploadForMorph('subject');
  }
}
