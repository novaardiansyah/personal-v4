<?php

namespace App\Services\AiAssistant;

use App\Models\Payment;
use App\Models\PaymentAccount;
use Illuminate\Support\Carbon;

class AiPaymentContextService
{
  public function getPaymentContextForUser(?int $userId = null): string
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return '';
    }

    $accounts = PaymentAccount::where('user_id', $userId)->get();
    $totalBalance = $accounts->sum('deposit');

    $accountsSummary = $accounts->map(function ($acc) {
      $depositFormatted = toIndonesianCurrency((float) $acc->deposit);
      return "- {$acc->name}: {$depositFormatted}";
    })->implode("\n");

    $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
    $endOfMonth   = Carbon::now()->endOfMonth()->format('Y-m-d');

    $recentPayments = Payment::where('user_id', $userId)
      ->with(['payment_type', 'category', 'payment_account', 'payment_account_to'])
      ->latest('date')
      ->latest('id')
      ->take(10)
      ->get();

    $monthlyIncome = Payment::where('user_id', $userId)
      ->where('type_id', 2)
      ->whereBetween('date', [$startOfMonth, $endOfMonth])
      ->sum('amount');

    $monthlyExpense = Payment::where('user_id', $userId)
      ->where('type_id', 1)
      ->whereBetween('date', [$startOfMonth, $endOfMonth])
      ->sum('amount');

    $transactionLines = [];
    foreach ($recentPayments as $p) {
      $typeName   = $p->payment_type?->name ?? ($p->type_id == 1 ? 'Expense' : ($p->type_id == 2 ? 'Income' : 'Transfer'));
      $category   = $p->category?->name ?? '-';
      $amountStr  = toIndonesianCurrency((float) $p->amount);
      $account    = $p->payment_account?->name ?? '-';
      $status     = $p->is_draft ? '[DRAFT]' : ($p->is_scheduled ? '[SCHEDULED]' : '[COMPLETED]');
      $dateStr    = Carbon::parse($p->date)->format('Y-m-d');

      $transactionLines[] = "- {$dateStr} | {$status} {$p->code} - {$p->name} ({$typeName} / {$category}): {$amountStr} via {$account}";
    }

    $transactionsFormatted = !empty($transactionLines) ? implode("\n", $transactionLines) : '- No recent transactions found.';

    $formattedIncome  = toIndonesianCurrency((float) $monthlyIncome);
    $formattedExpense = toIndonesianCurrency((float) $monthlyExpense);
    $formattedBalance = toIndonesianCurrency((float) $totalBalance);
    $currentMonthStr  = Carbon::now()->translatedFormat('F Y');

    return <<<CONTEXT
### 📊 User Real Financial & Payment Context ({$currentMonthStr})

**Account Balances (Total: {$formattedBalance}):**
{$accountsSummary}

**Monthly Overview ({$currentMonthStr}):**
- Total Income: {$formattedIncome}
- Total Expense: {$formattedExpense}

**Recent Transactions (Last 10):**
{$transactionsFormatted}
CONTEXT;
  }
}
