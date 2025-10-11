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

    $totals = Payment::whereBetween('date', [$startDate, $endDate])
      ->selectRaw("
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_expense,
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_withdrawal,
        SUM(CASE WHEN type_id = ? THEN amount ELSE 0 END) as total_transfer
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

    $totalBalance   = PaymentAccount::sum('deposit');
    $initialBalance = (int) $totalIncome + (int) $totalExpense;

    $percentIncome     = $totalIncome > 0 ? round(($totalIncome / $initialBalance) * 100, 2) : 0;
    $percentExpense    = $totalExpense > 0 ? round(($totalExpense / $initialBalance) * 100, 2) : 0;
    $percentWithdrawal = $totalWithdrawal > 0 ? round(($totalWithdrawal / $initialBalance) * 100, 2) : 0;
    $percentTransfer   = $totalTransfer > 0 ? round(($totalTransfer / $initialBalance) * 100, 2) : 0;

    return response()->json([
      'success' => true,
      'data' => [
        'total_balance'   => (int) $totalBalance,
        'initial_balance' => (int) $initialBalance,
        'income'          => (int) $totalIncome,
        'expenses'        => (int) $totalExpense,
        'withdrawal'      => (int) $totalWithdrawal,
        'transfer'        => (int) $totalTransfer,
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
    $limit = $request->get('limit', 10);

    $payments = Payment::with(['payment_type'])
      ->orderBy('updated_at', 'desc')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => PaymentResource::collection($payments),
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

      if ($this->processBase64Image($base64Data, $attachments, $uploadedAttachments, $errors)) {
        $this->updatePaymentAttachments($payment, $attachments);

        return $this->successResponse($payment, $attachments, 'Attachment added successfully', $uploadedAttachments);
      }
    }

    // Handle multiple upload
    if ($request->has('attachment_base64_array')) {
      $base64Array = $request->input('attachment_base64_array');

      foreach ($base64Array as $index => $base64Data) {
        $this->processBase64Image($base64Data, $attachments, $uploadedAttachments, $errors, $index);
      }

      if (!empty($uploadedAttachments)) {
        $this->updatePaymentAttachments($payment, $attachments);

        $message = count($uploadedAttachments) === 1
          ? 'Attachment added successfully'
          : count($uploadedAttachments) . ' attachments added successfully';

        return $this->successResponse($payment, $attachments, $message, $uploadedAttachments);
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
  private function processBase64Image($base64Data, &$attachments, &$uploadedAttachments, &$errors, $index = null)
  {
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
      $extension = strtolower($matches[1]);
      $base64Image = substr($base64Data, strpos($base64Data, ',') + 1);
      $imageData = base64_decode($base64Image);

      if ($imageData !== false) {
        $path = 'images/payment/' . Str::random(25) . '.' . $extension;

        try {
          Storage::disk('public')->put($path, $imageData);
          $attachments[] = $path;
          $uploadedAttachments[] = [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'index' => $index
          ];
          return true;
        } catch (\Exception $e) {
          $errorKey = $index !== null ? "attachment_{$index}" : "attachment";
          $errors[$errorKey] = "Failed to save image: " . $e->getMessage();
        }
      } else {
        $errorKey = $index !== null ? "attachment_{$index}" : "attachment";
        $errors[$errorKey] = "Invalid base64 data";
      }
    } else {
      $errorKey = $index !== null ? "attachment_{$index}" : "attachment";
      $errors[$errorKey] = "Invalid image format";
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
   * Return success response
   */
  private function successResponse($payment, $attachments, $message, $uploadedAttachments = [])
  {
    $attachmentUrls = array_map(function ($attachment) {
      return Storage::disk('public')->url($attachment);
    }, $attachments);

    return response()->json([
      'success' => true,
      'message' => $message,
      'data' => [
        'payment_id' => $payment->id,
        'attachments' => $attachmentUrls,
        'attachments_count' => count($uploadedAttachments)
      ]
    ]);
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
