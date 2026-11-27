<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Debt;

class GetDebtsOverviewTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_debts_overview';
  }

  public function getDescription(): string
  {
    return 'Retrieve user debts with principal, total payable, paid/unpaid installments, and upcoming due dates.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'status' => [
          'type'        => 'string',
          'description' => 'Filter by debt status (e.g. active, paid, overdue).',
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

    $query = Debt::where('user_id', $userId)->with(['payment_account', 'installments']);

    if (!empty($arguments['status'])) {
      $query->where('status', $arguments['status']);
    }

    $records = $query->latest('id')->take(20)->get();

    $debts = $records->map(function ($debt) {
      $paidInstallments   = $debt->installments->where('status', 'paid');
      $unpaidInstallments = $debt->installments->where('status', '!=', 'paid')->sortBy('due_date');
      $nextInstallment    = $unpaidInstallments->first();
      $paidAmount         = (float) $paidInstallments->sum('total_amount');
      $totalDebtAmount    = (float) $debt->installments->sum('total_amount');
      $remainingAmount    = max(0, $totalDebtAmount - $paidAmount);

      return [
        'code'                => $debt->code,
        'name'                => $debt->name,
        'status'              => $debt->status,
        'account'             => $debt->payment_account?->name ?? '-',
        'tenor'               => $debt->tenor,
        'paid_installments'   => $paidInstallments->count(),
        'total_installments'  => $debt->installments->count(),
        'paid_amount'         => $paidAmount,
        'formatted_paid'      => toIndonesianCurrency($paidAmount),
        'remaining_amount'    => $remainingAmount,
        'formatted_remaining' => toIndonesianCurrency($remainingAmount),
        'next_due_date'       => $nextInstallment?->due_date?->format('Y-m-d'),
        'next_due_amount'     => $nextInstallment ? toIndonesianCurrency((float) $nextInstallment->total_amount) : null,
      ];
    })->toArray();

    return [
      'count' => count($debts),
      'debts' => $debts,
    ];
  }
}
