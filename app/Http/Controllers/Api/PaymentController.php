<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentAccount;
use App\Http\Resources\PaymentResource;
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
      ->get();

    return response()->json([
      'success' => true,
      'data'    => PaymentResource::collection($transactions)
    ]);
  }
}
