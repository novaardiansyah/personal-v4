<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentAccount;
use App\Models\PaymentItem;
use App\Http\Resources\PaymentResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
  /**
   * Get financial summary for the current month
   *
   * ⚠️ MOBILE APP: Used by NovaApp - don't change response structure
   */
  public function summary(Request $request): JsonResponse
  {
    $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
    $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    $payments = Payment::whereBetween('date', [$startDate, $endDate])->get();

    $totalIncome = $payments->where('type_id', PaymentType::INCOME)->sum('amount');
    $totalExpense = $payments->where('type_id', PaymentType::EXPENSE)->sum('amount');
    $totalBalance = PaymentAccount::sum('deposit');

    return response()->json([
      'success' => true,
      'data' => [
        'total_balance' => $totalBalance,
        'income' => $totalIncome,
        'expenses' => $totalExpense,
        'period' => [
          'start_date' => $startDate,
          'end_date' => $endDate,
          'month' => Carbon::now()->format('F Y'),
        ],
        'savings' => $totalIncome - $totalExpense,
      ]
    ]);
  }

  /**
   * Get recent transactions for the user
   *
   * ⚠️ MOBILE APP: Used by NovaApp - don't change response structure
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

  /**
   * Get all payments with filters
   */
  public function index(Request $request): JsonResponse
  {
    $query = Payment::with(['payment_type', 'payment_account', 'payment_account_to']);

    // Apply filters
    $query->when($request->has('start_date'), function ($q) use ($request) {
      return $q->where('date', '>=', $request->start_date);
    });

    $query->when($request->has('end_date'), function ($q) use ($request) {
      return $q->where('date', '<=', $request->end_date);
    });

    $query->when($request->has('type_id'), function ($q) use ($request) {
      return $q->where('type_id', $request->type_id);
    });

    $query->when($request->has('payment_account_id'), function ($q) use ($request) {
      return $q->where('payment_account_id', $request->payment_account_id);
    });

    $query->when($request->has('is_scheduled'), function ($q) use ($request) {
      return $q->where('is_scheduled', $request->is_scheduled);
    });

    // Apply ordering
    $orderBy = $request->get('order_by', 'date');
    $orderDirection = $request->get('order_direction', 'desc');
    $query->orderBy($orderBy, $orderDirection);

    // Apply pagination
    $limit = $request->get('limit', 20);
    $payments = $query->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => PaymentResource::collection($payments)
    ]);
  }

  /**
   * Get specific payment
   */
  public function show(Request $request, $id): JsonResponse
  {
    $payment = Payment::with(['payment_type', 'payment_account', 'payment_account_to', 'items'])
      ->find($id);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data'    => new PaymentResource($payment)
    ]);
  }

  /**
   * Create new payment
   */
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'amount'                => 'required_if:has_items,false|nullable|numeric|min:0',
      'date'                  => 'required|date',
      'name'                  => 'required_if:has_items,false|nullable|string|max:255',
      'type_id'               => 'required|integer|exists:payment_types,id',
      'payment_account_id'    => 'required|integer|exists:payment_accounts,id',
      'payment_account_to_id' => 'nullable|integer|exists:payment_accounts,id|different:payment_account_id',
      'has_items'             => 'nullable|boolean',
      'has_charge'            => 'nullable|boolean',
      'is_scheduled'          => 'nullable|boolean',
      'attachments'           => 'nullable|array',
      'attachments.*'         => 'image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $data = $request->all();

    // Handle file uploads
    if ($request->hasFile('attachments')) {
      $attachments = [];
      foreach ($request->file('attachments') as $file) {
        $path = $file->store('images/payment', 'public');
        $attachments[] = $path;
      }
      $data['attachments'] = $attachments;
    }

    // Set default values for has_items = true
    if (!empty($data['has_items'])) {
      $data['amount'] = 0;
      $data['type_id'] = 1; // Set to expense type
      $data['has_charge'] = false;
      $data['name'] = null; // Will be populated when items are attached
    }

    // Use the same mutation logic as Filament
    $payment = new Payment();
    $mutate = $payment::mutateDataPayment($data);

    if (!$mutate['status']) {
      return response()->json([
        'success' => false,
        'message' => $mutate['message']
      ], 422);
    }

    $payment = Payment::create($mutate['data']);

    return response()->json([
      'success' => true,
      'message' => 'Payment created successfully',
      'data' => new PaymentResource($payment->load(['payment_type', 'payment_account', 'payment_account_to']))
    ], 201);
  }

  /**
   * Update payment
   */
  public function update(Request $request, $id): JsonResponse
  {
    $payment = Payment::find($id);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'amount'                => 'sometimes|required|numeric|min:0',
      'date'                  => 'sometimes|required|date',
      'name'                  => 'nullable|string|max:255',
      'type_id'               => 'sometimes|required|integer|exists:payment_types,id',
      'payment_account_id'    => 'sometimes|required|integer|exists:payment_accounts,id',
      'payment_account_to_id' => 'nullable|integer|exists:payment_accounts,id|different:payment_account_id',
      'is_scheduled'          => 'nullable|boolean',
      'attachments'           => 'nullable|array',
      'attachments.*'         => 'image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $data = $request->all();

    // Handle file uploads
    if ($request->hasFile('attachments')) {
      $attachments = [];
      foreach ($request->file('attachments') as $file) {
        $path = $file->store('images/payment', 'public');
        $attachments[] = $path;
      }
      $data['attachments'] = $attachments;
    }

    $payment->update($data);

    return response()->json([
      'success' => true,
      'message' => 'Payment updated successfully',
      'data' => new PaymentResource($payment->load(['payment_type', 'payment_account', 'payment_account_to']))
    ]);
  }

  /**
   * Delete payment
   */
  public function destroy(Request $request, $id): JsonResponse
  {
    $payment = Payment::find($id);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    // Delete attachment files
    if ($payment->attachments && is_array($payment->attachments)) {
      foreach ($payment->attachments as $attachment) {
        Storage::disk('public')->delete($attachment);
      }
    }

    $payment->delete();

    return response()->json([
      'success' => true,
      'message' => 'Payment deleted successfully'
    ]);
  }

  /**
   * Get payment accounts for dropdown
   */
  public function getPaymentAccounts(): JsonResponse
  {
    $accounts = PaymentAccount::select('id', 'name', 'deposit')
      ->orderBy('name')
      ->get()
      ->map(function ($account) {
        return [
          'id' => $account->id,
          'name' => $account->name,
          'deposit' => $account->deposit,
          'formatted_deposit' => toIndonesianCurrency($account->deposit)
        ];
      });

    return response()->json($accounts);
  }

  /**
   * Get payment types for dropdown
   */
  public function getPaymentTypes(): JsonResponse
  {
    $types = PaymentType::select('id', 'name')
      ->orderBy('id')
      ->get();

    return response()->json($types);
  }

  /**
   * Attach existing item to payment
   */
  public function attachItem(Request $request, $paymentId): JsonResponse
  {
    $payment = Payment::find($paymentId);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    if (!$payment->has_items) {
      return response()->json([
        'success' => false,
        'message' => 'This payment does not support items'
      ], 422);
    }

    $validator = Validator::make($request->all(), [
      'item_id' => 'required|integer|exists:items,id',
      'quantity' => 'required|integer|min:1',
      'price' => 'nullable|numeric|min:0'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    // Check if item already attached
    if ($payment->items()->where('item_id', $request->item_id)->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Item already attached to this payment'
      ], 422);
    }

    $data = $request->all();

    // Get item data
    $item = Item::find($data['item_id']);

    // Use item price if not provided
    $price = $data['price'] ?? $item->amount;
    $total = $price * $data['quantity'];

    // Generate item code
    $data['item_code'] = getCode('payment_item');

    // Attach item to payment
    $payment->items()->attach($data['item_id'], [
      'item_code' => $data['item_code'],
      'quantity' => $data['quantity'],
      'price' => $price,
      'total' => $total
    ]);

    // Update item price to match attachment price
    $item->update(['amount' => $price]);

    // Count total expense
    $expense = $payment->amount + $total;

    // Count deposit change (following Filament logic)
    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $expense;

    $is_scheduled = $payment->is_scheduled ?? false;

    // Update deposit payment account if not scheduled
    if (!$is_scheduled) {
      $payment->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    // Update payment notes
    $note = trim(($payment->name ?? '') . ', ' . "{$item->name} (x{$data['quantity']})", ', ');

    // Update payment
    $payment->update(['amount' => $expense, 'name' => $note]);

    return response()->json([
      'success' => true,
      'message' => 'Item attached successfully',
      'data' => [
        'amount' => $payment->amount,
        'formatted_amount' => toIndonesianCurrency($payment->amount),
        'items_count' => $payment->items()->count()
      ]
    ]);
  }

  /**
   * Create new item and attach to payment
   */
  public function createAndAttachItem(Request $request, $paymentId): JsonResponse
  {
    $payment = Payment::find($paymentId);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    if (!$payment->has_items) {
      return response()->json([
        'success' => false,
        'message' => 'This payment does not support items'
      ], 422);
    }

    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'type_id' => 'required|integer|exists:item_types,id',
      'quantity' => 'required|integer|min:1',
      'price' => 'required|numeric|min:0'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $data = $request->all();
    $total = $data['price'] * $data['quantity'];

    // Create new item
    $item = new Item();
    $item->name = $data['name'];
    $item->type_id = $data['type_id'];
    $item->amount = $data['price'];
    $item->code = getCode('item');
    $item->save();

    // Generate item code for pivot
    $itemCode = getCode('payment_item');

    // Attach item to payment
    $payment->items()->attach($item->id, [
      'item_code' => $itemCode,
      'quantity' => $data['quantity'],
      'price' => $data['price'],
      'total' => $total
    ]);

    // Count total expense
    $expense = $payment->amount + $total;

    // Count deposit change (following Filament logic)
    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $expense;

    $is_scheduled = $payment->is_scheduled ?? false;

    // Update deposit payment account if not scheduled
    if (!$is_scheduled) {
      $payment->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    // Update payment notes
    $note = trim(($payment->name ?? '') . ', ' . "{$item->name} (x{$data['quantity']})", ', ');

    // Update payment
    $payment->update(['amount' => $expense, 'name' => $note]);

    return response()->json([
      'success' => true,
      'message' => 'Item created and attached successfully',
      'data' => [
        'amount' => $payment->amount,
        'formatted_amount' => toIndonesianCurrency($payment->amount),
        'items_count' => $payment->items()->count()
      ]
    ]);
  }

  /**
   * Detach item from payment
   */
  public function detachItem(Request $request, $paymentId, $pivotId): JsonResponse
  {
    $payment = Payment::find($paymentId);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    // Find the pivot record
    $paymentItem = PaymentItem::where('payment_id', $paymentId)
      ->where('id', $pivotId)
      ->first();

    if (!$paymentItem) {
      return response()->json([
        'success' => false,
        'message' => 'Payment item not found'
      ], 404);
    }

    // Get the related item
    $item = $paymentItem->item;

    // Count expense after detach
    $expense = $payment->amount - $paymentItem->total;

    // Count deposit change (following Filament logic)
    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $expense;

    $is_scheduled = $payment->is_scheduled ?? false;

    // Update deposit payment account if not scheduled
    if (!$is_scheduled) {
      $payment->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    // Update payment notes
    $itemName = $item->name . ' (x' . $paymentItem->quantity . ')';
    $note = trim(implode(', ', array_diff(explode(', ', $payment->name ?? ''), [$itemName])));

    // Delete the pivot record and update payment
    $paymentItem->delete();
    $payment->update(['amount' => $expense, 'name' => $note]);

    return response()->json([
      'success' => true,
      'message' => 'Item detached successfully',
      'data' => [
        'amount' => $payment->amount,
        'formatted_amount' => toIndonesianCurrency($payment->amount),
        'items_count' => $payment->items()->count()
      ]
    ]);
  }

  /**
   * Get available items for attach
   */
  public function getAvailableItems(Request $request): JsonResponse
  {
    $query = Item::query();

    if ($request->has('search')) {
      $search = $request->search;
      $query->where('name', 'like', "%{$search}%")
        ->orWhere('code', 'like', "%{$search}%");
    }

    $items = $query->with('type')
      ->orderBy('name')
      ->get()
      ->map(function ($item) {
        return [
          'id' => $item->id,
          'name' => $item->name,
          'code' => $item->code,
          'amount' => $item->amount,
          'formatted_amount' => toIndonesianCurrency($item->amount),
          'type' => [
            'id' => $item->type->id,
            'name' => $item->type->name
          ]
        ];
      });

    return response()->json([
      'success' => true,
      'data' => $items
    ]);
  }

  /**
   * Get item types for dropdown
   */
  public function getItemTypes(): JsonResponse
  {
    $types = ItemType::select('id', 'name')
      ->orderBy('id')
      ->get();

    return response()->json([
      'success' => true,
      'data' => $types
    ]);
  }
}
