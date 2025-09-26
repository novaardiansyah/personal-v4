<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
  /**
   * Get financial summary for the current month
   */
  public function summary(Request $request): JsonResponse
  {
    $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
    $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
    $payments  = Payment::whereBetween('date', [$startDate, $endDate])->get();

    $totalIncome  = $payments->where('type_id', PaymentType::INCOME)->sum('amount');
    $totalExpense = $payments->where('type_id', PaymentType::EXPENSE)->sum('amount');
    $totalBalance = PaymentAccount::sum('deposit');

    return response()->json([
      'success' => true,
      'data' => [
        'total_balance' => $totalBalance,
        'income'        => $totalIncome,
        'expenses'      => $totalExpense,
        'period' => [
          'start_date' => $startDate,
          'end_date'   => $endDate,
          'month'      => Carbon::now()->format('F Y'),
        ],
        'savings' => $totalIncome - $totalExpense,
      ]
    ]);
  }

  /**
   * Get recent transactions for the user
   */
  public function recentTransactions(Request $request): JsonResponse
  {
    $limit = $request->get('limit', 10);

    $transactions = Payment::with(['payment_type', 'payment_account'])
      ->orderBy('date', 'desc')
      ->orderBy('created_at', 'desc')
      ->limit($limit)
      ->get()
      ->map(function ($payment) {
        $type = match($payment->type_id) {
          PaymentType::INCOME     => 'income',
          PaymentType::EXPENSE    => 'expense',
          PaymentType::TRANSFER   => 'transfer',
          PaymentType::WITHDRAWAL => 'withdrawal',
          default => 'unknown'
        };

        $amount = match($payment->type_id) {
          PaymentType::INCOME     => $payment->amount,
          PaymentType::EXPENSE    => -$payment->amount,
          PaymentType::TRANSFER   => $payment->amount,
          PaymentType::WITHDRAWAL => -$payment->amount,
          default => 0
        };

        return [
          'id'       => $payment->id,
          'title'    => $payment->name ?? $payment->payment_type->name,
          'amount'   => $amount,
          'type'     => strtolower($payment->payment_type->name),
          'date'     => $payment->date,
        ];
      });

    return response()->json([
      'success' => true,
      'data'    => $transactions
    ]);
  }
}
