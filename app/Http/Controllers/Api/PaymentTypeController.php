<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\PaymentType;
use App\Http\Resources\PaymentTypeResource;

class PaymentTypeController extends Controller
{
  /**
   * Get all payment types
   */
  public function index(): JsonResponse
  {
    $paymentTypes = PaymentType::orderBy('name')->get();
    return response()->json(PaymentTypeResource::collection($paymentTypes));
  }
}
