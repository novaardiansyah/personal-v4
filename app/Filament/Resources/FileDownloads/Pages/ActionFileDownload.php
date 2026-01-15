<?php

namespace App\Filament\Resources\FileDownloads\Pages;

use App\Filament\Resources\Files\Schemas\FileAction;
use Filament\Actions\Action;

class ActionFileDownload
{
  public static function upload_file(): Action
  {
    return FileAction::uploadForHasMany('file_download_id');
  }
}
