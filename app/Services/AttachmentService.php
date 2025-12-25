<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class AttachmentService
{
  public static function deleteAttachmentFiles(string|array $filepaths): bool
  {
    $filepaths = is_array($filepaths) ? $filepaths : [$filepaths];
    $sizes = ['small', 'medium', 'large', 'original'];
    $deleted = false;

    foreach ($filepaths as $filepath) {
      $basename = basename($filepath);

      foreach ($sizes as $size) {
        $replace = $size === 'original' ? $basename : $size . '-' . $basename;
        $newFilePath = str_replace($basename, $replace, $filepath);

        if (Storage::disk('public')->exists($newFilePath)) {
          Storage::disk('public')->delete($newFilePath);
          $deleted = true;
        }
      }
    }

    return $deleted;
  }
}
