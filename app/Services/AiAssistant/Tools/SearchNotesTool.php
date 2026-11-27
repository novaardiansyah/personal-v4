<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Note;
use Illuminate\Support\Str;

class SearchNotesTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'search_notes';
  }

  public function getDescription(): string
  {
    return 'Search personal notes by keyword in title, content, or code.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'search'           => [
          'type'        => 'string',
          'description' => 'Keyword to search in note title, content, or code.',
        ],
        'include_archived' => [
          'type'        => 'boolean',
          'description' => 'Include archived notes (default false).',
        ],
        'limit'            => [
          'type'        => 'integer',
          'description' => 'Max notes to return (default 10, max 20).',
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

    $query = Note::where('user_id', $userId);

    if (!($arguments['include_archived'] ?? false)) {
      $query->where('is_archived', false);
    }

    if (!empty($arguments['search'])) {
      $term = $arguments['search'];
      $query->where(function ($q) use ($term) {
        $q->where('title', 'like', "%{$term}%")
          ->orWhere('content', 'like', "%{$term}%")
          ->orWhere('code', 'like', "%{$term}%");
      });
    }

    $limit   = min((int) ($arguments['limit'] ?? 10), 20);
    $records = $query->orderByDesc('is_pinned')->latest('updated_at')->take($limit)->get();

    $notes = $records->map(function ($note) {
      return [
        'code'       => $note->code,
        'title'      => $note->title,
        'snippet'    => Str::limit(strip_tags((string) $note->content), 300),
        'is_pinned'  => (bool) $note->is_pinned,
        'updated_at' => $note->updated_at?->format('Y-m-d H:i'),
      ];
    })->toArray();

    return [
      'count' => count($notes),
      'notes' => $notes,
    ];
  }
}
