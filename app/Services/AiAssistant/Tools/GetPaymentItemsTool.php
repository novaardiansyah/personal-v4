<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\PaymentItem;

class GetPaymentItemsTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_payment_items';
  }

  public function getDescription(): string
  {
    return 'Search itemized purchase records inside payment transactions by item name, month, or year.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'search' => [
          'type'        => 'string',
          'description' => 'Keyword to match against item name.',
        ],
        'month'  => [
          'type'        => 'integer',
          'description' => 'Transaction month (1-12).',
        ],
        'year'   => [
          'type'        => 'integer',
          'description' => 'Transaction year.',
        ],
        'limit'  => [
          'type'        => 'integer',
          'description' => 'Max items to return (default 10, max 25).',
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

    $query = PaymentItem::query()
      ->with(['item', 'payment'])
      ->whereHas('payment', function ($q) use ($userId, $arguments) {
        $q->where('user_id', $userId);
        if (!empty($arguments['month'])) {
          $q->whereMonth('date', (int) $arguments['month']);
        }
        if (!empty($arguments['year'])) {
          $q->whereYear('date', (int) $arguments['year']);
        }
      });

    if (!empty($arguments['search'])) {
      $term = $arguments['search'];
      $query->whereHas('item', function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%");
      });
    }

    $limit   = min((int) ($arguments['limit'] ?? 10), 25);
    $records = $query->latest('id')->take($limit)->get();

    $items = $records->map(function ($pi) {
      return [
        'item_name'       => $pi->item?->name ?? '-',
        'quantity'        => (float) $pi->quantity,
        'price'           => (float) $pi->price,
        'formatted_price' => toIndonesianCurrency((float) $pi->price),
        'total'           => (float) $pi->total,
        'formatted_total' => toIndonesianCurrency((float) $pi->total),
        'payment_code'    => $pi->payment?->code ?? '-',
        'payment_date'    => $pi->payment?->date ?? null,
      ];
    })->toArray();

    return [
      'count'       => count($items),
      'total_spent' => toIndonesianCurrency((float) $records->sum('total')),
      'items'       => $items,
    ];
  }
}
