<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadController extends Controller
{
  public function index(Request $request, $path, $extension)
  {
    $directory = $request->query('directory', '');
    $filePath = storage_path("app/{$directory}/{$path}.{$extension}");

    if (!file_exists($filePath)) {
      abort(404, 'File not found.');
    }

    $fileName = trim(basename($filePath));

    return response()->download($filePath, $fileName);
  }
}
