<?php

namespace App\Services;

use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
  public static function deleteAttachmentFiles(string|array $filepaths): void
  {
    $filepaths = is_array($filepaths) ? $filepaths : [$filepaths];
    $sizes = ['small', 'medium', 'large', 'original'];

    foreach ($filepaths as $filepath) {
      $basename = basename($filepath);

      foreach ($sizes as $size) {
        $replace = $size === 'original' ? $basename : $size . '-' . $basename;
        $newFilePath = str_replace($basename, $replace, $filepath);

        Gallery::where('file_path', $newFilePath)
          ->get()
          ->each->forceDelete();

        if (Storage::disk('public')->exists($newFilePath)) {
          Storage::disk('public')->delete($newFilePath);
        }
      }
    }
  }
}
