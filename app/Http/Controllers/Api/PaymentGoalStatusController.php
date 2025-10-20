<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGoalStatus;
use Illuminate\Http\JsonResponse;

class PaymentGoalStatusController extends Controller
{
  /**
   * Display a listing of payment goal statuses for dropdown.
   */
  public function index(): JsonResponse
  {
    $statuses = PaymentGoalStatus::all()->map(function ($status) {
      return [
        'id' => $status->id,
        'name' => $status->name,
        'badge_color' => $status->getBadgeColors(),
      ];
    });

    return response()->json([
      'success' => true,
      'message' => 'Payment goal statuses retrieved successfully',
      'data' => $statuses
    ]);
  }
}