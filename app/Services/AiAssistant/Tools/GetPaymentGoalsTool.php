<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\PaymentGoal;

class GetPaymentGoalsTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_payment_goals';
  }

  public function getDescription(): string
  {
    return 'Retrieve user savings and payment goals with current progress, target amounts, and target dates.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'search' => [
          'type'        => 'string',
          'description' => 'Optional search keyword for goal name or code.',
        ],
        'limit'  => [
          'type'        => 'integer',
          'description' => 'Max goals to return (default 10, max 25).',
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

    $query = PaymentGoal::where('user_id', $userId)->with('status');

    if (!empty($arguments['search'])) {
      $term = $arguments['search'];
      $query->where(function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%")
          ->orWhere('code', 'like', "%{$term}%");
      });
    }

    $limit   = min((int) ($arguments['limit'] ?? 10), 25);
    $records = $query->latest('id')->take($limit)->get();

    $goals = $records->map(function ($g) {
      $remaining = max(0, (float) $g->target_amount - (float) $g->amount);
      return [
        'code'               => $g->code,
        'name'               => $g->name,
        'status'             => $g->status?->name ?? '-',
        'current_amount'     => (float) $g->amount,
        'formatted_current'  => toIndonesianCurrency((float) $g->amount),
        'target_amount'      => (float) $g->target_amount,
        'formatted_target'   => toIndonesianCurrency((float) $g->target_amount),
        'remaining_amount'   => $remaining,
        'formatted_remaining'=> toIndonesianCurrency($remaining),
        'progress_percent'   => $g->target_amount > 0 ? round(($g->amount / $g->target_amount) * 100, 2) : 0,
        'target_date'        => $g->target_date?->format('Y-m-d'),
      ];
    })->toArray();

    return [
      'count' => count($goals),
      'goals' => $goals,
    ];
  }
}
