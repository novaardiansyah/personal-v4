<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Subscription;

class GetSubscriptionsTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_subscriptions';
  }

  public function getDescription(): string
  {
    return 'List user recurring subscriptions, billing cycles, next renewal dates, and estimated monthly/annual spend.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'include_paused' => [
          'type'        => 'boolean',
          'description' => 'Include paused subscriptions (default false).',
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

    $includePaused = (bool) ($arguments['include_paused'] ?? false);

    $query = Subscription::where('user_id', $userId)
      ->with(['payment_account', 'category'])
      ->orderBy('next_date');

    if (!$includePaused) {
      $query->where('is_paused', false);
    }

    $records            = $query->get();
    $monthlyEquivalent  = 0.0;

    $subscriptions = $records->map(function ($sub) use (&$monthlyEquivalent) {
      $amount = (float) $sub->amount;
      if (!$sub->is_paused) {
        $monthlyEquivalent += match ($sub->cycle) {
          'yearly'    => $amount / 12,
          'quarterly' => $amount / 3,
          default     => $amount,
        };
      }

      return [
        'code'             => $sub->code,
        'name'             => $sub->name,
        'cycle'            => $sub->cycle,
        'amount'           => $amount,
        'formatted_amount' => toIndonesianCurrency($amount),
        'next_date'        => $sub->next_date?->format('Y-m-d'),
        'account'          => $sub->payment_account?->name ?? '-',
        'category'         => $sub->category?->name ?? '-',
        'is_paused'        => (bool) $sub->is_paused,
      ];
    })->toArray();

    return [
      'count'                        => count($subscriptions),
      'estimated_monthly_spend'      => round($monthlyEquivalent, 2),
      'formatted_monthly_equivalent' => toIndonesianCurrency($monthlyEquivalent),
      'formatted_annual_equivalent'  => toIndonesianCurrency($monthlyEquivalent * 12),
      'subscriptions'                => $subscriptions,
    ];
  }
}
