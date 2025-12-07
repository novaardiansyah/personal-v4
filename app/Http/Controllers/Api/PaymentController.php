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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
      'endDate'   => 'nullable|date_format:Y-m-d|after_or_equal:startDate',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    if ($request->has('startDate') && $request->has('endDate')) {
      $startDate = $request->input('startDate');
      $endDate   = $request->input('endDate');
    } else {
      $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
      $endDate   = Carbon::now()->endOfMonth()->format('Y-m-d');
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

    $totalIncome     = $totals->total_income ?? 0;
    $totalExpense    = $totals->total_expense ?? 0;
    $totalWithdrawal = $totals->total_withdrawal ?? 0;
    $totalTransfer   = $totals->total_transfer ?? 0;

    $totalBalance   = PaymentAccount::where('user_id', Auth()->user()->id)->sum('deposit');
    $initialBalance = (int) $totalIncome + (int) $totalExpense;

    $percentIncome     = $totalIncome > 0 ? round(($totalIncome / $initialBalance) * 100, 2) : 0;
    $percentExpense    = $totalExpense > 0 ? round(($totalExpense / $initialBalance) * 100, 2) : 0;
    $percentWithdrawal = $totalWithdrawal > 0 ? round(($totalWithdrawal / $initialBalance) * 100, 2) : 0;
    $percentTransfer   = $totalTransfer > 0 ? round(($totalTransfer / $initialBalance) * 100, 2) : 0;

    $scheduled_expense     = (int) ($totals->scheduled_expense ?? 0);
    $total_balance         = (int) $totalBalance;
    $total_after_scheduled = $total_balance - $scheduled_expense;

    return response()->json([
      'success' => true,
      'data' => [
        'total_balance'         => $total_balance,
        'scheduled_expense'     => $scheduled_expense,
        'total_after_scheduled' => $total_after_scheduled,
        'initial_balance'       => (int) $initialBalance,
        'income'                => (int) $totalIncome,
        'expenses'              => (int) $totalExpense,
        'withdrawal'            => (int) $totalWithdrawal,
        'transfer'              => (int) $totalTransfer,
        'percents' => [
          'income'        => (float) $percentIncome,
          'expenses'      => (float) $percentExpense,
          'withdrawal'    => (float) $percentWithdrawal,
          'transfer'      => (float) $percentTransfer,
        ],
        'period' => [
          'start_date' => $startDate,
          'end_date'   => $endDate,
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
      'page'       => 'nullable|integer|min:1',
      'limit'      => 'nullable|integer|min:1',
      'date_from'  => 'nullable|date',
      'date_to'    => 'nullable|date',
      'type'       => 'nullable|integer|exists:payment_types,id',
      'account_id' => 'nullable|integer|exists:payment_accounts,id'
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

    $validated  = $validator->validated();
    $limit      = $validated['limit'] ?? 10;
    $date_from  = $validated['date_from'] ?? null;
    $date_to    = $validated['date_to'] ?? null;
    $type       = $validated['type'] ?? null;
    $account_id = $validated['account_id'] ?? null;

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

    $payments = $payments
      ->orderBy('updated_at', 'desc')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data'    => PaymentResource::collection($payments),
      'pagination' => [
        'current_page' => $payments->currentPage(),
        'from'         => $payments->firstItem(),
        'last_page'    => $payments->lastPage(),
        'per_page'     => $payments->perPage(),
        'to'           => $payments->lastItem(),
        'total'        => $payments->total(),
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
      'data'    => new PaymentResource($payment)
    ]);
  }

  /**
   * Create new payment
   */
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'amount'                => 'required_if:has_items,false|nullable|numeric',
      'date'                  => 'required|date',
      'name'                  => 'required_if:has_items,false|nullable|string|max:255',
      'type_id'               => 'required|integer|exists:payment_types,id',
      'payment_account_id'    => 'required|integer|exists:payment_accounts,id',
      'payment_account_to_id' => 'required_if:type_id,3,4|nullable|integer|exists:payment_accounts,id|different:payment_account_id',
      'has_items'             => 'nullable|boolean',
      'has_charge'            => 'nullable|boolean',
      'is_scheduled'          => 'nullable|boolean',
      'attachments'           => 'nullable|array',
      'attachments.*'         => 'image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);

    $validator->setAttributeNames([
      'name'                  => 'description',
      'type_id'               => 'category',
      'payment_account_id'    => 'payment account',
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
      'payment_account_to_id' => 'required_if:type_id,3,4|nullable|integer|exists:payment_accounts,id|different:payment_account_id',
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

  public function attachMultipleItems(Request $request, Payment $payment): JsonResponse
  {
    if (!$payment->has_items) {
      return response()->json([
        'success' => false,
        'message' => 'This payment does not support items'
      ], 422);
    }

    $validator = Validator::make($request->all(), [
      'items'           => 'required|array|min:1',
      'items.*.item_id' => 'nullable|integer|exists:items,id',
      'items.*.name'    => 'required|string|max:255',
      'items.*.amount'  => 'required|numeric|min:0',
      'items.*.qty'     => 'required|integer|min:1',
      'totalAmount'     => 'required|numeric|min:0'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $items         = $request->items;
    $totalAmount   = $request->totalAmount;
    $attachedItems = [];

    foreach ($items as $itemData) {
      if (empty($itemData['item_id'])) {
        $existingItem = Item::where('name', $itemData['name'])->first();

        if ($existingItem) {
          $item->update(['amount' => $itemData['amount']]);
          $item = $existingItem;
        } else {
          $item = Item::create([
            'name'    => $itemData['name'],
            'amount'  => $itemData['amount'],
            'type_id' => 1,
            'code'    => getCode('item')
          ]);
        }

        $itemData['item_id'] = $item->id;
      } else {
        $item = Item::find($itemData['item_id']);
      }

      $price    = $itemData['amount'];
      $quantity = $itemData['qty'];
      $total    = $price * $quantity;
      $itemCode = getCode('payment_item');

      $payment->items()->attach($itemData['item_id'], [
        'item_code' => $itemCode,
        'quantity'  => $quantity,
        'price'     => $price,
        'total'     => $total
      ]);

      $attachedItems[] = [
        'item_id'   => $item->id,
        'name'      => $item->name,
        'quantity'  => $quantity,
        'price'     => $price,
        'total'     => $total,
        'item_code' => $itemCode
      ];
    }

    $expense         = $payment->amount + $totalAmount;
    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $expense;
    $is_scheduled    = $payment->is_scheduled ?? false;

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
      'price'    => 'nullable|numeric|min:0'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $item        = $paymentItem->item;
    $oldQuantity = $paymentItem->quantity;
    $oldPrice    = $paymentItem->price;
    $oldTotal    = $paymentItem->total;

    $newQuantity = $request->input('quantity');
    $newPrice    = $request->input('price', $oldPrice);
    $newTotal    = $newPrice * $newQuantity;

    $paymentItem->update([
      'quantity' => $newQuantity,
      'price'    => $newPrice,
      'total'    => $newTotal
    ]);

    if ($newPrice !== $oldPrice) {
      $item->update(['amount' => $newPrice]);
    }

    $totalDifference = $newTotal - $oldTotal;
    $newExpense      = $payment->amount + $totalDifference;

    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $newExpense;
    $is_scheduled    = $payment->is_scheduled ?? false;

    if (!$is_scheduled) {
      $payment->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    $oldItemName = "{$item->name} (x{$oldQuantity})";
    $newItemName = "{$item->name} (x{$newQuantity})";
    $note        = $payment->name ?? '';
    $note        = str_replace($oldItemName, $newItemName, $note);

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

    $item            = $paymentItem->item;
    $expense         = $payment->amount - $paymentItem->total;
    $adjustedDeposit = $payment->payment_account->deposit + $payment->amount - $expense;
    $is_scheduled    = $payment->is_scheduled ?? false;

    if (!$is_scheduled) {
      $payment->payment_account->update(['deposit' => $adjustedDeposit]);
    }

    $itemName = $item->name . ' (x' . $paymentItem->quantity . ')';
    $note     = trim(implode(', ', array_diff(explode(', ', $payment->name ?? ''), [$itemName])));

    $paymentItem->delete();
    $payment->update(['amount' => $expense, 'name' => $note]);

    return response()->json([
      'success' => true,
      'message' => 'Item detached successfully',
      'data' => [
        'amount'           => $payment->amount,
        'formatted_amount' => toIndonesianCurrency($payment->amount),
        'items_count'      => $payment->items()->count()
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
      'data'    => PaymentItemResource::collection($attachedItems),
      'pagination' => [
        'current_page' => $attachedItems->currentPage(),
        'from'         => $attachedItems->firstItem(),
        'last_page'    => $attachedItems->lastPage(),
        'per_page'     => $attachedItems->perPage(),
        'to'           => $attachedItems->lastItem(),
        'total'        => $attachedItems->total(),
      ]
    ]);
  }

  /**
   * Get payment items summary
   */
  public function getPaymentItemsSummary(Request $request, Payment $payment): JsonResponse
  {
    $items      = $payment->items()->get();
    $totalItems = $items->count();
    $totalQty   = $items->sum('pivot.quantity');

    $totalAmount = $items->sum(function ($item) {
      return $item->pivot->quantity * $item->pivot->price;
    });

    return response()->json([
      'success' => true,
      'data' => [
        'payment_id'       => $payment->id,
        'payment_code'     => $payment->code,
        'total_items'      => $totalItems,
        'total_qty'        => $totalQty,
        'total_amount'     => $totalAmount,
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
        'from'         => $items->firstItem(),
        'last_page'    => $items->lastPage(),
        'per_page'     => $items->perPage(),
        'to'           => $items->lastItem(),
        'total'        => $items->total(),
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
        'from'         => $items->firstItem(),
        'last_page'    => $items->lastPage(),
        'per_page'     => $items->perPage(),
        'to'           => $items->lastItem(),
        'total'        => $items->total(),
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
      'data'    => PaymentAttachmentResource::collection($attachmentData)
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
      $extension        = $info['extension'];       // png
      $nameOnly         = $info['filename'];        // file

      $mediumName = "medium-{$nameOnly}.{$extension}";
      $mediumPath = "images/payment/{$mediumName}";

      $disk = Storage::disk('public');

      // Check for optimized version first (like in getAttachments())
      $filepath = $disk->exists($mediumPath) ? $mediumPath : "images/payment/{$filenameOriginal}";

      $url = Storage::disk('public')->url($filepath);

      if ($disk->exists($filepath)) {
        $attachmentData[] = (object) [
          'id'        => $index + 1,
          'url'       => $url,
          'filepath'  => $filepath,
          'filename'  => basename($filepath),
          'extension' => $extension,
          'size'      => $disk->size($filepath),
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

    $filepath    = $request->input('filepath');
    $attachments = $payment->attachments ?? [];

    $size = ['small', 'medium', 'large', 'original'];
    foreach ($size as $row) {
      $filepath = str_replace($row . '-', '', $filepath);
    }
    
    $attachmentIndex  = null;
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
        $basename    = basename($filepath);
        $replace     = $row === 'original' ? $basename : $row . '-' . $basename;
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
}
