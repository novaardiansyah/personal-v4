<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\ContactMessage;
use App\Models\Email;
use Illuminate\Support\Str;

class GetRecentCommunicationsTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_recent_communications';
  }

  public function getDescription(): string
  {
    return 'Summarize recent inbound contact messages and outbound emails.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'limit' => [
          'type'        => 'integer',
          'description' => 'Max records per channel (default 5, max 15).',
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

    $limit = min((int) ($arguments['limit'] ?? 5), 15);

    $contactMessages = ContactMessage::latest('id')
      ->take($limit)
      ->get()
      ->map(function ($cm) {
        return [
          'name'       => $cm->name,
          'email'      => $cm->email,
          'subject'    => $cm->subject ?? '-',
          'snippet'    => Str::limit((string) $cm->message, 200),
          'is_read'    => (bool) ($cm->is_read ?? false),
          'created_at' => $cm->created_at?->format('Y-m-d H:i'),
        ];
      })
      ->toArray();

    $emails = Email::latest('id')
      ->take($limit)
      ->get()
      ->map(function ($em) {
        return [
          'recipient'  => $em->email,
          'name'       => $em->name,
          'subject'    => $em->subject,
          'status'     => $em->status?->value ?? (string) $em->status,
          'created_at' => $em->created_at?->format('Y-m-d H:i'),
        ];
      })
      ->toArray();

    return [
      'unread_contact_count' => ContactMessage::where('is_read', false)->count(),
      'contact_messages'     => $contactMessages,
      'recent_emails'        => $emails,
    ];
  }
}
