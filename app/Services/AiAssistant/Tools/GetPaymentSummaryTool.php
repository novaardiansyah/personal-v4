<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Payment;
use Illuminate\Support\Carbon;

class GetPaymentSummaryTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_payment_summary';
  }

  public function getDescription(): string
  {
    return 'Aggregate total income, total expense, net cashflow, and category breakdown for a given month and year (defaults to current month/year).';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'month' => [
          'type'        => 'integer',
          'description' => 'Month (1-12). Defaults to current month.',
        ],
        'year'  => [
          'type'        => 'integer',
          'description' => 'Year (e.g. 2026). Defaults to current year.',
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

    $month = !empty($arguments['month']) ? (int) $arguments['month'] : (int) now()->month;
    $year  = !empty($arguments['year']) ? (int) $arguments['year'] : (int) now()->year;

    $baseQuery = Payment::where('user_id', $userId)
      ->whereMonth('date', $month)
      ->whereYear('date', $year)
      ->where('is_draft', false);

    $totalExpense = (float) (clone $baseQuery)->where('type_id', 1)->sum('amount');
    $totalIncome  = (float) (clone $baseQuery)->where('type_id', 2)->sum('amount');
    $netCashflow  = $totalIncome - $totalExpense;
    $txCount      = (int) (clone $baseQuery)->count();

    $expenseByCategory = (clone $baseQuery)
      ->where('type_id', 1)
      ->with('category')
      ->get()
      ->groupBy(fn ($p) => $p->category?->name ?? 'Uncategorized')
      ->map(function ($group, $categoryName) {
        $sum = (float) $group->sum('amount');
        return [
          'category'         => $categoryName,
          'count'            => $group->count(),
          'total'            => $sum,
          'formatted_total'  => toIndonesianCurrency($sum),
        ];
      })
      ->sortByDesc('total')
      ->values()
      ->toArray();

    return [
      'period'                  => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
      'transaction_count'       => $txCount,
      'total_income'            => $totalIncome,
      'formatted_total_income'  => toIndonesianCurrency($totalIncome),
      'total_expense'           => $totalExpense,
      'formatted_total_expense' => toIndonesianCurrency($totalExpense),
      'net_cashflow'            => $netCashflow,
      'formatted_net_cashflow'  => toIndonesianCurrency($netCashflow),
      'expense_by_category'     => $expenseByCategory,
    ];
  }
}
