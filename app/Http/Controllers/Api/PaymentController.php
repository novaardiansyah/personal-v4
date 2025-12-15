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
use App\Http\Resources\PaymentItemResource;
use App\Http\Resources\ItemResource;
use App\Http\Resources\PaymentAttachmentResource;
use App\Jobs\PaymentResource\DailyReportJob;
use App\Jobs\PaymentResource\MonthlyReportJob;
use App\Jobs\PaymentResource\PaymentReportPdf;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
  /**
   * Get financial summary for the current month
   *
   * ⚠️ MOBILE APP: Used by NovaApp - don't change response structure
   */
  public function summary(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'startDate' => 'nullable|date_format:Y-m-d',
      'endDate' => 'nullable|date_format:Y-m-d|after_or_equal:startDate',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    if ($request->has('startDate') && $request->has('endDate')) {
      $startDate = $request->input('startDate');
      $endDate = $request->input('endDate');
    } else {
      $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
      $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    $totals = Payment::where('user_id', Auth()->user()->id)
      ->whereBetween('date', [$startDate, $endDate])
      ->selectRaw("
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_expense,
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_withdrawal,
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_transfer,
        SUM(CASE WHEN type_id = 1 AND is_scheduled = 1 THEN amount ELSE 0 END) AS scheduled_expense
      ", [
        PaymentType::INCOME,
        PaymentType::EXPENSE,
        PaymentType::WITHDRAWAL,
        PaymentType::TRANSFER
      ])
      ->first();

    $totalIncome = $totals->total_income ?? 0;
    $totalExpense = $totals->total_expense ?? 0;
    $totalWithdrawal = $totals->total_withdrawal ?? 0;
    $totalTransfer = $totals->total_transfer ?? 0;

    $totalBalance = PaymentAccount::where('user_id', Auth()->user()->id)->sum('deposit');
    $initialBalance = (int) $totalIncome + (int) $totalExpense;

    $percentIncome = $totalIncome > 0 ? round(($totalIncome / $initialBalance) * 100, 2) : 0;
    $percentExpense = $totalExpense > 0 ? round(($totalExpense / $initialBalance) * 100, 2) : 0;
    $percentWithdrawal = $totalWithdrawal > 0 ? round(($totalWithdrawal / $initialBalance) * 100, 2) : 0;
    $percentTransfer = $totalTransfer > 0 ? round(($totalTransfer / $initialBalance) * 100, 2) : 0;

    $scheduled_expense = (int) ($totals->scheduled_expense ?? 0);
    $total_balance = (int) $totalBalance;
    $total_after_scheduled = $total_balance - $scheduled_expense;

    return response()->json([
      'success' => true,
      'data' => [
        'total_balance' => $total_balance,
        'scheduled_expense' => $scheduled_expense,
        'total_after_scheduled' => $total_after_scheduled,
        'initial_balance' => (int) $initialBalance,
        'income' => (int) $totalIncome,
        'expenses' => (int) $totalExpense,
        'withdrawal' => (int) $totalWithdrawal,
        'transfer' => (int) $totalTransfer,
        'percents' => [
          'income' => (float) $percentIncome,
          'expenses' => (float) $percentExpense,
          'withdrawal' => (float) $percentWithdrawal,
          'transfer' => (float) $percentTransfer,
        ],
        'period' => [
          'start_date' => $startDate,
          'end_date' => $endDate,
        ],
      ]
    ]);
  }


  /**
   * Get all payments with pagination
   *
   * ⚠️ MOBILE APP: Used by NovaApp - don't change response structure
   */
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'page' => 'nullable|integer|min:1',
      'limit' => 'nullable|integer|min:1',
      'date_from' => 'nullable|date',
      'date_to' => 'nullable|date',
      'type' => 'nullable|integer|exists:payment_types,id',
      'account_id' => 'nullable|integer|exists:payment_accounts,id',
      'search' => 'nullable|string|max:255',
    ]);

    $validator->after(function ($validator) use ($request) {
      $dateFrom = $request->input('date_from');
      $dateTo = $request->input('date_to');

      if ($dateFrom && $dateTo) {
        if (strtotime($dateFrom) > strtotime($dateTo)) {
          $validator->errors()->add('date_from', 'The date from field must be a date before or equal to date to.');
        }
      }
    });

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $limit = $validated['limit'] ?? 10;
    $date_from = $validated['date_from'] ?? null;
    $date_to = $validated['date_to'] ?? null;
    $type = $validated['type'] ?? null;
    $account_id = $validated['account_id'] ?? null;
    $search = $validated['search'] ?? null;

    $payments = Payment::with(['payment_type'])
      ->where('user_id', Auth()->user()->id);

    if ($date_from) {
      $payments->where('date', '>=', $date_from);
    }

    if ($date_to) {
      $payments->where('date', '<=', $date_to);
    }

    if ($type) {
      $payments->where('type_id', $type);
    }

    if ($account_id) {
      $payments->where('payment_account_id', $account_id);
    }

    if ($search) {
      $payments->where('name', 'like', '%' . $search . '%');
    }

    $payments = $payments
      ->orderBy('updated_at', 'desc')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => PaymentResource::collection($payments),
      'pagination' => [
        'current_page' => $payments->currentPage(),
        'from' => $payments->firstItem(),
        'last_page' => $payments->lastPage(),
        'per_page' => $payments->perPage(),
        'to' => $payments->lastItem(),
        'total' => $payments->total(),
      ]
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
      'data' => new PaymentResource($payment)
    ]);
  }

  /**
   * Update existing payment
   *
   * For payments with items: only name and date can be edited (amount is ignored).
   * For regular payments: amount, name, and date can be edited.
   *
   * ⚠️ MOBILE APP: Used by NovaApp - don't change response structure
   */
  public function update(Request $request, $id): JsonResponse
  {
    $payment = Payment::with(['payment_account', 'payment_account_to'])
      ->where('user_id', Auth()->user()->id)
      ->find($id);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'amount' => 'nullable|numeric|min:0',
      'name' => 'required|string|max:255',
      'date' => 'required|date',
      'type_id' => 'nullable|integer|exists:payment_types,id',
      'payment_account_id' => 'nullable|integer|exists:payment_accounts,id',
      'payment_account_to_id' => 'nullable|integer|exists:payment_accounts,id',
    ]);

    $validator->setAttributeNames([
      'name' => 'description',
      'type_id' => 'category',
      'payment_account_id' => 'payment account',
      'payment_account_to_id' => 'to payment account',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $data = $validator->validated();

    if ($payment->has_items) {
      $payment->update([
        'name' => $data['name'],
        'date' => $data['date'],
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Payment updated successfully',
        'data' => []
      ]);
    }

    // Use reusable model method for balance mutation
    $mutate = Payment::mutateDataPaymentUpdate($payment, $data);

    if (!$mutate['status']) {
      return response()->json([
        'success' => false,
        'message' => $mutate['message']
      ], 422);
    }

    $payment->update([
      'amount' => intval($data['amount'] ?? $payment->amount),
      'name' => $data['name'],
      'date' => $data['date'],
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Payment updated successfully',
      'data' => []
    ]);
  }

  /**
   * @OA\Post(
   *     path="/api/payments",
   *     summary="Create new payment",
   *     tags={"Payments"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PaymentStoreRequest")),
   *     @OA\Response(response=201, description="Payment created successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'amount' => 'required_if:has_items,false|nullable|numeric',
      'date' => 'required|date',
      'name' => 'required_if:has_items,false|nullable|string|max:255',
      'type_id' => 'required|integer|exists:payment_types,id',
      'payment_account_id' => 'required|integer|exists:payment_accounts,id',
      'payment_account_to_id' => 'required_if:type_id,3,4|nullable|integer|exists:payment_accounts,id|different:payment_account_id',
      'has_items' => 'nullable|boolean',
      'has_charge' => 'nullable|boolean',
      'is_scheduled' => 'nullable|boolean',
      'is_draft' => 'nullable|boolean',
      'request_view' => 'nullable|boolean',
    ]);

    $validator->setAttributeNames([
      'name' => 'description',
      'type_id' => 'category',
      'payment_account_id' => 'payment account',
      'payment_account_to_id' => 'to payment account',
    ]);

    $validator->setCustomMessages([
      'name.required_if' => 'The :attribute field is required when the payment has no items.',
      'payment_account_to_id.required_if' => 'The :attribute field is required when the category is transfer or widrawal.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $data = $validator->validated();

    if (!empty($data['has_items'])) {
      $data['amount'] = 0;
      $data['type_id'] = 1;
      $data['has_charge'] = false;
      $data['name'] = null;
    }

    $payment = new Payment();
    $mutate = $payment::mutateDataPayment($data);

    if (!$mutate['status']) {
      return response()->json([
        'success' => false,
        'message' => $mutate['message']
      ], 422);
    }

    $payment = Payment::create($mutate['data']);
    $payment->request_view = $data['request_view'] ?? false;

    return response()->json([
      'success' => true,
      'message' => 'Payment created successfully',
      'data' => new PaymentResource($payment->load(['payment_type', 'payment_account', 'payment_account_to']))
    ], 201);
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

    if ($payment->items()->where('item_id', $request->item_id)->exists()) {
      return response()->json([
        'success' => false,
        'message' => 'Item already attached to this payment'
      ], 422);
    }

    $data = $request->all();

    $item = Item::find($data['item_id']);

    $price = $data['price'] ?? $item->amount;
    $total = $price * $data['quantity'];

    $data['item_code'] = getCode('payment_item');

    $payment->items()->attach($data['item_id'], [
      'item_code' => $data['item_code'],
      'quantity' => $data['quantity'],
      'price' => $price,
      'total' => $total
    ]);

    // Use PaymentService for consistent logic with Filament
    $result = PaymentService::afterItemAttach($payment, $item, [
      'quantity' => $data['quantity'],
      'price' => $price,
      'total' => $total,
      'has_charge' => $data['has_charge'] ?? false,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Item attached successfully',
      'data' => [
        'amount' => $result['amount'],
        'formatted_amount' => $result['formatted_amount'],
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

    $item = Item::create([
      'name' => $data['name'],
      'type_id' => $data['type_id'],
      'amount' => $data['price'],
      'code' => getCode('item')
    ]);

    $itemCode = getCode('payment_item');

    $payment->items()->attach($item->id, [
      'item_code' => $itemCode,
      'quantity' => $data['quantity'],
      'price' => $data['price'],
      'total' => $total
    ]);

    $result = PaymentService::afterItemAttach($payment, $item, [
      'quantity' => $data['quantity'],
      'price' => $data['price'],
      'total' => $total,
      'has_charge' => $data['has_charge'] ?? false,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Item created and attached successfully',
      'data' => [
        'amount' => $result['amount'],
        'formatted_amount' => $result['formatted_amount'],
        'items_count' => $payment->items()->count()
      ]
    ]);
  }

  public function attachMultipleItems(Request $request, Payment $payment): JsonResponse
  {
    if (!$payment->has_items) {
      return response()->json([
        'success' => false,
        'message' => 'This payment does not support items'
      ], 422);
    }

    $validator = Validator::make($request->all(), [
      'items' => 'required|array|min:1',
      'items.*.item_id' => 'nullable|integer|exists:items,id',
      'items.*.name' => 'required|string|max:255',
      'items.*.amount' => 'required|numeric|min:0',
      'items.*.qty' => 'required|integer|min:1',
      'totalAmount' => 'required|numeric|min:0'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $items = $request->items;
    $totalAmount = $request->totalAmount;
    $attachedItems = [];

    foreach ($items as $itemData) {
      if (empty($itemData['item_id'])) {
        $existingItem = Item::where('name', $itemData['name'])->first();

        if ($existingItem) {
          $item->update(['amount' => $itemData['amount']]);
          $item = $existingItem;
        } else {
          $item = Item::create([
            'name' => $itemData['name'],
            'amount' => $itemData['amount'],
            'type_id' => 1,
            'code' => getCode('item')
          ]);
        }

        $itemData['item_id'] = $item->id;
      } else {
        $item = Item::find($itemData['item_id']);
      }

      $price = $itemData['amount'];
      $quantity = $itemData['qty'];
      $total = $price * $quantity;
      $itemCode = getCode('payment_item');

      $payment->items()->attach($itemData['item_id'], [
        'item_code' => $itemCode,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $total
      ]);

      $attachedItems[] = [
        'item_id' => $item->id,
        'name' => $item->name,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $total,
        'item_code' => $itemCode
      ];
    }

    $expense = $payment->amount + $totalAmount;
    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $expense;
    $is_scheduled = $payment->is_scheduled ?? false;

    if (!$is_scheduled) {
      $payment->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    $itemNotes = [];
    foreach ($attachedItems as $attachedItem) {
      $itemNotes[] = "{$attachedItem['name']} (x{$attachedItem['quantity']})";
    }
    $note = trim(($payment->name ?? '') . ', ' . implode(', ', $itemNotes), ', ');

    $payment->update(['amount' => $expense, 'name' => $note]);

    return response()->json([
      'success' => true,
      'message' => 'Multiple items attached successfully',
      'data' => [
        'id' => $payment->id,
        'name' => $payment->name,
        'amount' => $payment->amount,
        'formatted_amount' => toIndonesianCurrency($payment->amount),
      ]
    ]);
  }

  /**
   * Update item quantity in payment
   */
  public function updateItem(Request $request, Payment $payment, $pivotId): JsonResponse
  {
    $paymentItem = PaymentItem::where('payment_id', $payment->id)
      ->where('id', $pivotId)
      ->first();

    if (!$paymentItem) {
      return response()->json([
        'success' => false,
        'message' => 'Payment item not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
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

    $item = $paymentItem->item;
    $oldQuantity = $paymentItem->quantity;
    $oldPrice = $paymentItem->price;
    $oldTotal = $paymentItem->total;

    $newQuantity = $request->input('quantity');
    $newPrice = $request->input('price', $oldPrice);
    $newTotal = $newPrice * $newQuantity;

    $paymentItem->update([
      'quantity' => $newQuantity,
      'price' => $newPrice,
      'total' => $newTotal
    ]);

    if ($newPrice !== $oldPrice) {
      $item->update(['amount' => $newPrice]);
    }

    $totalDifference = $newTotal - $oldTotal;
    $newExpense = $payment->amount + $totalDifference;

    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $newExpense;
    $is_scheduled = $payment->is_scheduled ?? false;

    if (!$is_scheduled) {
      $payment->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    $oldItemName = "{$item->name} (x{$oldQuantity})";
    $newItemName = "{$item->name} (x{$newQuantity})";
    $note = $payment->name ?? '';
    $note = str_replace($oldItemName, $newItemName, $note);

    $payment->update(['amount' => $newExpense, 'name' => $note]);

    return response()->json([
      'success' => true,
      'message' => 'Item quantity updated successfully'
    ]);
  }

  /**
   * Detach item from payment
   */
  public function detachItem(Payment $payment, $pivotId): JsonResponse
  {
    $paymentItem = PaymentItem::where('payment_id', $payment->id)
      ->where('id', $pivotId)
      ->first();

    if (!$paymentItem) {
      return response()->json([
        'success' => false,
        'message' => 'Payment item not found'
      ], 404);
    }

    $item = $paymentItem->item;

    $result = PaymentService::beforeItemDetach($payment, $item, [
      'quantity' => $paymentItem->quantity,
      'total' => $paymentItem->total,
      'has_charge' => false,
    ]);

    $paymentItem->delete();

    return response()->json([
      'success' => true,
      'message' => 'Item detached successfully',
      'data' => [
        'amount' => $result['amount'],
        'formatted_amount' => $result['formatted_amount'],
        'items_count' => $payment->items()->count()
      ]
    ]);
  }

  /**
   * Get attached items for payment
   */
  public function getAttachedItems(Request $request, $paymentId): JsonResponse
  {
    $payment = Payment::find($paymentId);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    $limit = $request->get('limit', 10);

    $attachedItems = $payment->items()
      ->with('type')
      ->orderBy('pivot_created_at', 'desc')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => PaymentItemResource::collection($attachedItems),
      'pagination' => [
        'current_page' => $attachedItems->currentPage(),
        'from' => $attachedItems->firstItem(),
        'last_page' => $attachedItems->lastPage(),
        'per_page' => $attachedItems->perPage(),
        'to' => $attachedItems->lastItem(),
        'total' => $attachedItems->total(),
      ]
    ]);
  }

  /**
   * Get payment items summary
   */
  public function getPaymentItemsSummary(Request $request, Payment $payment): JsonResponse
  {
    $items = $payment->items()->get();
    $totalItems = $items->count();
    $totalQty = $items->sum('pivot.quantity');

    $totalAmount = $items->sum(function ($item) {
      return $item->pivot->quantity * $item->pivot->price;
    });

    return response()->json([
      'success' => true,
      'data' => [
        'payment_id' => $payment->id,
        'payment_code' => $payment->code,
        'total_items' => $totalItems,
        'total_qty' => $totalQty,
        'total_amount' => $totalAmount,
        'formatted_amount' => toIndonesianCurrency($totalAmount),
      ]
    ]);
  }

  /**
   * Get items not yet attached to specific payment
   */
  public function getItemsNotAttached(Request $request, $paymentId): JsonResponse
  {
    $payment = Payment::find($paymentId);

    if (!$payment) {
      return response()->json([
        'success' => false,
        'message' => 'Payment not found'
      ], 404);
    }

    // Get IDs of items already attached to this payment
    $attachedItemIds = $payment->items()->pluck('items.id')->toArray();

    $query = Item::query();

    if ($request->has('search')) {
      $search = $request->search;
      $query->where('name', 'like', "%{$search}%")
        ->orWhere('code', 'like', "%{$search}%");
    }

    // Exclude items that are already attached
    if (!empty($attachedItemIds)) {
      $query->whereNotIn('id', $attachedItemIds);
    }

    $limit = $request->get('limit', 10);

    $items = $query->with('type')
      ->orderBy('updated_at', 'desc')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => ItemResource::collection($items),
      'pagination' => [
        'current_page' => $items->currentPage(),
        'from' => $items->firstItem(),
        'last_page' => $items->lastPage(),
        'per_page' => $items->perPage(),
        'to' => $items->lastItem(),
        'total' => $items->total(),
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

    $limit = $request->get('limit', 10);

    $items = $query->with('type')
      ->orderBy('name')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => ItemResource::collection($items),
      'pagination' => [
        'current_page' => $items->currentPage(),
        'from' => $items->firstItem(),
        'last_page' => $items->lastPage(),
        'per_page' => $items->perPage(),
        'to' => $items->lastItem(),
        'total' => $items->total(),
      ]
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

  /**
   * Get list of attachments for a payment
   *
   * @param Request $request
   * @param int $paymentId
   * @return JsonResponse
   */
  public function getAttachments(Request $request, Payment $payment): JsonResponse
  {
    $attachments = $payment->attachments ?? [];
    $attachmentData = $this->formatAttachmentsForResponse($attachments);

    return response()->json([
      'success' => empty($attachmentData) ? false : true,
      'message' => empty($attachmentData) ? 'No attachments found' : 'Attachments found',
      'data' => PaymentAttachmentResource::collection($attachmentData)
    ]);
  }

  /**
   * Add attachment to payment using base64
   *
   * @param Request $request
   * @param int $paymentId
   * @return JsonResponse
   */
  public function addAttachment(Request $request, Payment $payment): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'attachment_base64' => 'required_without:attachment_base64_array|string',
      'attachment_base64_array' => 'required_without:attachment_base64|array',
      'attachment_base64_array.*' => 'string'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $attachments = $payment->attachments ?? [];
    $uploadedAttachments = [];
    $errors = [];

    // Handle single upload
    if ($request->has('attachment_base64')) {
      $base64Data = $request->input('attachment_base64');

      if ($this->processBase64Upload($base64Data, $attachments, $uploadedAttachments, $errors)) {
        $this->updatePaymentAttachments($payment, $attachments);

        return $this->attachmentSuccessResponse($payment, $attachments, 'Attachment added successfully', $uploadedAttachments);
      }
    }

    // Handle multiple upload
    if ($request->has('attachment_base64_array')) {
      $base64Array = $request->input('attachment_base64_array');

      foreach ($base64Array as $index => $base64Data) {
        $this->processBase64Upload($base64Data, $attachments, $uploadedAttachments, $errors, $index);
      }

      if (!empty($uploadedAttachments)) {
        $this->updatePaymentAttachments($payment, $attachments);

        $message = count($uploadedAttachments) === 1
          ? 'Attachment added successfully'
          : count($uploadedAttachments) . ' attachments added successfully';

        return $this->attachmentSuccessResponse($payment, $attachments, $message, $uploadedAttachments);
      }
    }

    return response()->json([
      'success' => false,
      'message' => 'Invalid image format',
      'errors' => $errors
    ], 422);
  }

  /**
   * Process base64 image and add to attachments
   */
  private function processBase64Upload($base64Data, &$attachments, &$uploadedAttachments, &$errors, $index = null)
  {
    try {
      // Use UtilsHelper::processBase64Image() to process the image
      $path = processBase64Image($base64Data, 'images/payment');

      if ($path) {
        // Apply image optimization like in CreatePayment.php afterCreate()
        $optimizedPaths = uploadAndOptimize($path, 'public', 'images/payment');

        // Store the optimized paths (all versions will be stored)
        $attachments[] = $optimizedPaths['original'];

        // Get the medium version for return (like in getAttachments())
        $mediumPath = $optimizedPaths['medium'];
        $mediumUrl = Storage::disk('public')->url($mediumPath);

        $uploadedAttachments[] = [
          'path' => $optimizedPaths['original'],
          'medium_path' => $mediumPath,
          'url' => $mediumUrl,
          'index' => $index,
          'optimized_paths' => $optimizedPaths
        ];
        return true;
      } else {
        $errorKey = $index !== null ? "attachment_{$index}" : "attachment";
        $errors[$errorKey] = "Failed to process image";
      }
    } catch (\Exception $e) {
      $errorKey = $index !== null ? "attachment_{$index}" : "attachment";
      $errors[$errorKey] = "Failed to save image: " . $e->getMessage();
    }

    return false;
  }

  /**
   * Update payment attachments
   */
  private function updatePaymentAttachments($payment, $attachments)
  {
    $payment->attachments = $attachments;
    $payment->save();
  }

  /**
   * Format attachments for response (usable for both addAttachment and getAttachments)
   */
  private function formatAttachmentsForResponse($attachments)
  {
    $attachmentData = [];

    foreach ($attachments as $index => $attachment) {
      $info = pathinfo($attachment);

      $filenameOriginal = $info['basename'];        // file.png
      $extension = $info['extension'];       // png
      $nameOnly = $info['filename'];        // file

      $mediumName = "medium-{$nameOnly}.{$extension}";
      $mediumPath = "images/payment/{$mediumName}";

      $disk = Storage::disk('public');

      // Check for optimized version first (like in getAttachments())
      $filepath = $disk->exists($mediumPath) ? $mediumPath : "images/payment/{$filenameOriginal}";

      $url = Storage::disk('public')->url($filepath);

      if ($disk->exists($filepath)) {
        $attachmentData[] = (object) [
          'id' => $index + 1,
          'url' => $url,
          'filepath' => $filepath,
          'filename' => basename($filepath),
          'extension' => $extension,
          'size' => $disk->size($filepath),
        ];
      }
    }

    return $attachmentData;
  }

  /**
   * Return attachment success response
   */
  private function attachmentSuccessResponse($payment, $attachments, $message, $uploadedAttachments = [])
  {
    $attachmentData = $this->formatAttachmentsForResponse($attachments);

    return response()->json([
      'success' => true,
      'message' => $message,
      'data' => [
        'payment_id' => $payment->id,
        'attachments' => $attachmentData,
        'attachments_count' => count($uploadedAttachments)
      ]
    ]);
  }

  /**
   * Delete attachment from payment
   *
   * @param Request $request
   * @param Payment $payment
   * @return JsonResponse
   */
  public function deleteAttachment(Request $request, Payment $payment): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'filepath' => 'required|string'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $filepath = $request->input('filepath');
    $attachments = $payment->attachments ?? [];

    $size = ['small', 'medium', 'large', 'original'];
    foreach ($size as $row) {
      $filepath = str_replace($row . '-', '', $filepath);
    }

    $attachmentIndex = null;
    $attachmentExists = false;

    foreach ($attachments as $index => $attachment) {
      if ($attachment === $filepath) {
        $attachmentIndex = $index;
        $attachmentExists = true;
        break;
      }
    }

    if (!$attachmentExists) {
      return response()->json([
        'success' => false,
        'message' => 'Attachment not found'
      ], 404);
    }

    try {
      foreach ($size as $row) {
        $basename = basename($filepath);
        $replace = $row === 'original' ? $basename : $row . '-' . $basename;
        $newFilePath = str_replace($basename, $replace, $filepath);

        if (Storage::disk('public')->exists($newFilePath)) {
          Storage::disk('public')->delete($newFilePath);
        }
      }

      unset($attachments[$attachmentIndex]);
      $attachments = array_values($attachments); // Re-index the array

      $payment->attachments = $attachments;
      $payment->save();

      return response()->json([
        'success' => true,
        'message' => 'Attachment deleted successfully',
        'data' => [
          'payment_id' => $payment->id,
          'attachments_count' => count($attachments)
        ]
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Failed to delete attachment: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Delete a payment
   *
   * @param Request $request
   * @param Payment $payment
   * @return JsonResponse
   */
  public function destroy(Request $request, Payment $payment): JsonResponse
  {
    $payment->delete();

    return response()->json([
      'success' => true,
      'message' => 'Payment deleted successfully'
    ]);
  }

  /**
   * Generate payment report (PDF or Email)
   *
   * ⚠️ MOBILE APP: Used by NovaApp - don't change response structure
   */
  public function generateReport(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'report_type' => 'required|in:daily,monthly,date_range',
      'start_date' => 'required_if:report_type,date_range|nullable|date_format:Y-m-d',
      'end_date' => 'required_if:report_type,date_range|nullable|date_format:Y-m-d|after_or_equal:start_date',
      'periode' => 'required_if:report_type,monthly|nullable|date_format:Y-m',
    ]);

    $validator->setCustomMessages([
      'start_date.required_if' => 'The start date is required for custom date range report.',
      'end_date.required_if' => 'The end date is required for custom date range report.',
      'end_date.after_or_equal' => 'The end date must be after or equal to start date.',
      'periode.required_if' => 'The periode (month) is required for monthly report.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $user = $request->user();
    $validated = $validator->validated();

    $reportType = $validated['report_type'];

    match ($reportType) {
      'daily' => DailyReportJob::dispatch(),
      'monthly' => MonthlyReportJob::dispatch([
        'periode' => $validated['periode'],
        'user' => $user,
      ]),
      default => PaymentReportPdf::dispatch([
        'start_date' => $validated['start_date'],
        'end_date' => $validated['end_date'],
        'user' => $user,
      ]),
    };

    $messages = [
      'daily' => 'Daily report will be sent to your email.',
      'monthly' => 'Monthly report will be sent to your email.',
      'date_range' => 'Custom report will be sent to your email.',
    ];

    return response()->json([
      'success' => true,
      'message' => $messages[$reportType] ?? 'Report is being processed.'
    ]);
  }

  /**
   * Get AI-friendly payment summary with aggregated statistics
   *
   * This endpoint is optimized for AI agents to generate summaries
   * without transferring full transaction details.
   *
   * ⚠️ AI AGENT: Used by AI Agent (n8n) - optimized for minimal data transfer
   */
  public function aiSummary(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'search' => 'nullable|string|max:255',
      'type' => 'nullable|integer|exists:payment_types,id',
      'account_id' => 'nullable|integer|exists:payment_accounts,id',
      'date_from' => 'nullable|date',
      'date_to' => 'nullable|date|after_or_equal:date_from',
      'limit' => 'nullable|integer|min:1|max:100',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $limit = $validated['limit'] ?? 50;

    logger('validated', $validated);

    $query = Payment::where('payments.user_id', Auth()->user()->id);

    if (!empty($validated['search'])) {
      $query->where('payments.name', 'like', '%' . $validated['search'] . '%');
    }

    if (!empty($validated['type'])) {
      $query->where('type_id', $validated['type']);
    }

    if (!empty($validated['account_id'])) {
      $query->where('payment_account_id', $validated['account_id']);
    }

    if (!empty($validated['date_from'])) {
      $query->where('date', '>=', $validated['date_from']);
    }

    if (!empty($validated['date_to'])) {
      $query->where('date', '<=', $validated['date_to']);
    }

    $statistics = $query->selectRaw('
      COUNT(*) as total_transactions,
      SUM(amount) as total_amount,
      AVG(amount) as average_amount,
      MIN(amount) as min_amount,
      MAX(amount) as max_amount
    ')->first();

    $breakdownByType = (clone $query)
      ->join('payment_types', 'payments.type_id', '=', 'payment_types.id')
      ->selectRaw('
        payment_types.name as type,
        payments.type_id,
        COUNT(*) as count,
        SUM(payments.amount) as total
      ')
      ->groupBy('payments.type_id', 'payment_types.name')
      ->get();

    $breakdownByAccount = (clone $query)
      ->join('payment_accounts', 'payments.payment_account_id', '=', 'payment_accounts.id')
      ->selectRaw('
        payment_accounts.id as account_id,
        payment_accounts.name as account_name,
        COUNT(*) as count,
        SUM(payments.amount) as total
      ')
      ->groupBy('payment_accounts.id', 'payment_accounts.name')
      ->orderByDesc('total')
      ->get();

    $breakdownByMonth = (clone $query)
      ->selectRaw("
        DATE_FORMAT(date, '%Y-%m') as month,
        DATE_FORMAT(date, '%M %Y') as month_name,
        COUNT(*) as count,
        SUM(amount) as total
      ")
      ->groupBy('month', 'month_name')
      ->orderByDesc('month')
      ->get();

    $topTransactions = (clone $query)
      ->join('payment_accounts', 'payments.payment_account_id', '=', 'payment_accounts.id')
      ->select(
        'payments.id',
        'payments.name',
        'payments.date',
        'payments.amount',
        'payment_accounts.name as account'
      )
      ->orderByDesc('payments.amount')
      ->limit($limit)
      ->get();

    $filters = [];
    if (!empty($validated['search'])) {
      $filters['search'] = $validated['search'];
    }
    if (!empty($validated['type'])) {
      $type = PaymentType::find($validated['type']);
      $filters['type'] = $type ? $type->name : null;
    }
    if (!empty($validated['account_id'])) {
      $account = PaymentAccount::find($validated['account_id']);
      $filters['account'] = $account ? $account->name : null;
    }

    return response()->json([
      'success' => true,
      'data' => [
        'period' => [
          'start' => $validated['date_from'] ?? null,
          'end' => $validated['date_to'] ?? null,
        ],
        'filters' => $filters,
        'statistics' => [
          'total_transactions' => (int) ($statistics->total_transactions ?? 0),
          'total_amount' => (int) ($statistics->total_amount ?? 0),
          'average_amount' => (int) ($statistics->average_amount ?? 0),
          'min_amount' => (int) ($statistics->min_amount ?? 0),
          'max_amount' => (int) ($statistics->max_amount ?? 0),
        ],
        'breakdown_by_type' => $breakdownByType->map(fn($item) => [
          'type' => $item->type,
          'type_id' => $item->type_id,
          'count' => (int) $item->count,
          'total' => (int) $item->total,
        ]),
        'breakdown_by_account' => $breakdownByAccount->map(fn($item) => [
          'account_id' => $item->account_id,
          'account_name' => $item->account_name,
          'count' => (int) $item->count,
          'total' => (int) $item->total,
        ]),
        'breakdown_by_month' => $breakdownByMonth->map(fn($item) => [
          'month' => $item->month,
          'month_name' => $item->month_name,
          'count' => (int) $item->count,
          'total' => (int) $item->total,
        ]),
        'top_transactions' => $topTransactions->map(fn($item) => [
          'id' => $item->id,
          'name' => $item->name,
          'date' => $item->date,
          'amount' => (int) $item->amount,
          'account' => $item->account,
        ]),
      ]
    ]);
  }
}
