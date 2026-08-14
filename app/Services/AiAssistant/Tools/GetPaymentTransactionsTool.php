<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Payment;
use Illuminate\Support\Carbon;

class GetPaymentTransactionsTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_payment_transactions';
  }

  public function getDescription(): string
  {
    return 'Search and retrieve user financial payment transactions filtered by month, year, transaction type (expense, income, transfer), search keyword, or chronological order.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'month' => [
          'type'        => 'integer',
          'description' => 'Transaction month (1-12). Example: 7 for July.',
        ],
        'year' => [
          'type'        => 'integer',
          'description' => 'Transaction year. Example: 2026.',
        ],
        'type' => [
          'type'        => 'string',
          'enum'        => ['expense', 'income', 'transfer'],
          'description' => 'Transaction type: expense, income, or transfer.',
        ],
        'search' => [
          'type'        => 'string',
          'description' => 'Search keyword for transaction name or code.',
        ],
        'sort' => [
          'type'        => 'string',
          'enum'        => ['asc', 'desc'],
          'description' => 'Chronological sort order. "asc" for oldest/first transaction, "desc" for newest/latest transaction.',
        ],
        'limit' => [
          'type'        => 'integer',
          'description' => 'Maximum number of transaction records to retrieve (default 5, max 20).',
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

    $query = Payment::where('user_id', $userId)
      ->with(['payment_type', 'category', 'payment_account']);

    if (!empty($arguments['month'])) {
      $query->whereMonth('date', (int) $arguments['month']);
    }

    if (!empty($arguments['year'])) {
      $query->whereYear('date', (int) $arguments['year']);
    }

    if (!empty($arguments['type'])) {
      $typeStr = strtolower($arguments['type']);
      if ($typeStr === 'expense') {
        $query->where('type_id', 1);
      } elseif ($typeStr === 'income') {
        $query->where('type_id', 2);
      } elseif ($typeStr === 'transfer') {
        $query->whereIn('type_id', [3, 4]);
      }
    }

    if (!empty($arguments['search'])) {
      $term = $arguments['search'];
      $query->where(function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%")
          ->orWhere('code', 'like', "%{$term}%");
      });
    }

    $sortDir = strtolower($arguments['sort'] ?? 'desc');
    $query->orderBy('date', $sortDir)->orderBy('id', $sortDir);

    $limit   = min((int) ($arguments['limit'] ?? 5), 20);
    $records = $query->take($limit)->get();

    $results = $records->map(function ($p) {
      return [
        'id'               => $p->id,
        'code'             => $p->code,
        'date'             => Carbon::parse($p->date)->format('Y-m-d'),
        'name'             => $p->name,
        'type'             => $p->payment_type?->name ?? ($p->type_id == 1 ? 'Expense' : 'Income'),
        'category'         => $p->category?->name ?? '-',
        'account'          => $p->payment_account?->name ?? '-',
        'amount'           => (float) $p->amount,
        'formatted_amount' => toIndonesianCurrency((float) $p->amount),
        'is_draft'         => (bool) $p->is_draft,
        'is_scheduled'     => (bool) $p->is_scheduled,
      ];
    })->toArray();

    return [
      'count'        => count($results),
      'transactions' => $results,
    ];
  }
}
