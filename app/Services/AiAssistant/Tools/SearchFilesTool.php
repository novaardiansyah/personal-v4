<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\File;

class SearchFilesTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'search_files';
  }

  public function getDescription(): string
  {
    return 'Search uploaded files and generated PDFs by filename, description, or code.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'search' => [
          'type'        => 'string',
          'description' => 'Keyword to search in file name, alias, description, or code.',
        ],
        'limit'  => [
          'type'        => 'integer',
          'description' => 'Max files to return (default 10, max 20).',
        ],
      ],
      'required'   => [],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $query = File::where('user_id', $userId)
      ->where('has_been_deleted', false)
      ->with('type');

    if (!empty($arguments['search'])) {
      $term = $arguments['search'];
      $query->where(function ($q) use ($term) {
        $q->where('file_name', 'like', "%{$term}%")
          ->orWhere('file_alias', 'like', "%{$term}%")
          ->orWhere('description', 'like', "%{$term}%")
          ->orWhere('code', 'like', "%{$term}%");
      });
    }

    $limit   = min((int) ($arguments['limit'] ?? 10), 20);
    $records = $query->latest('id')->take($limit)->get();

    $files = $records->map(function ($file) {
      return [
        'code'         => $file->code,
        'file_name'    => $file->file_alias ?: $file->file_name,
        'type'         => $file->type?->name ?? '-',
        'file_size'    => $file->file_size,
        'download_url' => $file->download_url,
        'created_at'   => $file->created_at?->format('Y-m-d H:i'),
      ];
    })->toArray();

    return [
      'count' => count($files),
      'files' => $files,
    ];
  }
}
