<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class AttachmentService
{
  public static function deleteAttachmentFiles(string|array $filepaths): void
  {
    $filepaths = is_array($filepaths) ? $filepaths : [$filepaths];

    foreach ($filepaths as $filepath) {
      if (Storage::disk('public')->exists($filepath)) {
        Storage::disk('public')->delete($filepath);
      }
    }
  }
}
