<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentAccount;
use App\Http\Resources\PaymentAccountResource;

class PaymentAccountController extends Controller
{
  /**
   * Get all payment accounts
   */
  public function index(): JsonResponse
  {
    $accounts = PaymentAccount::orderBy('name')->get();
    return response()->json(PaymentAccountResource::collection($accounts));
  }

  /**
   * Create new payment account
   */
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255|unique:payment_accounts,name',
      'deposit' => 'required|numeric|min:0'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $account = PaymentAccount::create([
      'name' => $request->name,
      'deposit' => $request->deposit
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Payment account created successfully',
      'data' => new PaymentAccountResource($account)
    ], 201);
  }

  /**
   * Update payment account
   */
  public function update(Request $request, $id): JsonResponse
  {
    $account = PaymentAccount::find($id);

    if (!$account) {
      return response()->json([
        'success' => false,
        'message' => 'Payment account not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'name' => 'sometimes|required|string|max:255|unique:payment_accounts,name,' . $id,
      'deposit' => 'sometimes|required|numeric|min:0'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $account->update($request->all());

    return response()->json([
      'success' => true,
      'message' => 'Payment account updated successfully',
      'data' => new PaymentAccountResource($account)
    ]);
  }

  /**
   * Delete payment account
   */
  public function destroy(Request $request, $id): JsonResponse
  {
    $account = PaymentAccount::find($id);

    if (!$account) {
      return response()->json([
        'success' => false,
        'message' => 'Payment account not found'
      ], 404);
    }

    // Check if account has related payments
    if ($account->payments()->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Cannot delete account that has associated payments'
      ], 422);
    }

    $account->delete();

    return response()->json([
      'success' => true,
      'message' => 'Payment account deleted successfully'
    ]);
  }

  /**
   * Audit payment account deposit
   */
  public function audit(Request $request, PaymentAccount $paymentAccount): JsonResponse
  {
    $validator = $this->getAuditValidator($request, $paymentAccount);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $paymentAccount->audit($request->deposit);
    $paymentAccount = $paymentAccount->refresh();

    $data = PaymentAccountResource::collection([$paymentAccount]);

    return response()->json([
      'success' => true,
      'message' => 'Audit completed successfully',
      'data'    => $data[0] ?? []
    ]);
  }

  /**
   * Get validator for audit request
   */
  private function getAuditValidator(Request $request, PaymentAccount $paymentAccount)
  {
    return Validator::make($request->all(), [
      'deposit' => [
        'required',
        'numeric',
        'min:0',
        function ($attribute, $value, $fail) use ($paymentAccount) {
          if ((int) $value === (int) $paymentAccount->deposit) {
            $fail('Deposit amount is the same as the current deposit');
          }
        }
      ]
    ]);
  }
}
