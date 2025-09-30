<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PaymentType;

class PaymentTypeController extends Controller
{
  /**
   * Get all payment types
   */
  public function index(): JsonResponse
  {
    $paymentTypes = PaymentType::orderBy('name')->get();

    return response()->json([
      'success' => true,
      'message' => 'Payment types retrieved successfully',
      'data' => $paymentTypes
    ]);
  }
}