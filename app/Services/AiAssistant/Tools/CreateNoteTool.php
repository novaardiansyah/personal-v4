<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Note;

class CreateNoteTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'create_note';
  }

  public function getDescription(): string
  {
    return 'Propose saving a new personal note. Requires user confirmation before saving.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'title'     => [
          'type'        => 'string',
          'description' => 'Note title.',
        ],
        'content'   => [
          'type'        => 'string',
          'description' => 'Note content in Markdown or plain text.',
        ],
        'is_pinned' => [
          'type'        => 'boolean',
          'description' => 'Whether to pin the note.',
        ],
      ],
      'required'   => ['title', 'content'],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $title   = trim((string) ($arguments['title'] ?? ''));
    $content = trim((string) ($arguments['content'] ?? ''));
    if ($title === '' || $content === '') {
      return ['status' => false, 'message' => 'Note title and content are required.'];
    }

    return [
      'status'                => true,
      'requires_confirmation' => true,
      'summary'               => "Save note: \"{$title}\"",
      'proposed_data'         => [
        'title'     => $title,
        'content'   => $content,
        'is_pinned' => (bool) ($arguments['is_pinned'] ?? false),
      ],
    ];
  }

  public function executeConfirmed(array $arguments, ?int $userId = null): array
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $note = Note::create([
      'user_id'     => $userId,
      'title'       => trim((string) $arguments['title']),
      'content'     => trim((string) $arguments['content']),
      'is_pinned'   => (bool) ($arguments['is_pinned'] ?? false),
      'is_archived' => false,
    ]);

    return [
      'status'  => true,
      'message' => "Note {$note->code} ({$note->title}) saved.",
      'record'  => [
        'id'    => $note->id,
        'code'  => $note->code,
        'title' => $note->title,
      ],
    ];
  }
}
