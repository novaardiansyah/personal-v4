<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PaymentGoalResource;
use App\Http\Resources\Api\PaymentGoalCollection;
use App\Models\PaymentGoal;
use App\Models\PaymentGoalStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentGoalController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request): JsonResponse
  {
    $query = PaymentGoal::with('status')->latest();

    // Filter by status
    if ($request->has('status_id')) {
      $query->where('status_id', $request->status_id);
    }

    // Search by name, description, or code
    if ($request->has('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('description', 'like', "%{$search}%")
          ->orWhere('code', 'like', "%{$search}%");
      });
    }

    // Filter by date range
    if ($request->has('start_date_from')) {
      $query->where('start_date', '>=', $request->start_date_from);
    }
    if ($request->has('start_date_to')) {
      $query->where('start_date', '<=', $request->start_date_to);
    }
    if ($request->has('target_date_from')) {
      $query->where('target_date', '>=', $request->target_date_from);
    }
    if ($request->has('target_date_to')) {
      $query->where('target_date', '<=', $request->target_date_to);
    }

    // Filter by amount range
    if ($request->has('amount_min')) {
      $query->where('amount', '>=', $request->amount_min);
    }
    if ($request->has('amount_max')) {
      $query->where('amount', '<=', $request->amount_max);
    }

    // Include trashed records if requested
    if ($request->has('with_trashed') && $request->boolean('with_trashed')) {
      $query->withTrashed();
    }

    // Pagination with custom per_page
    $perPage = $request->get('per_page', 15);
    $perPage = min($perPage, 100); // Max 100 items per page

    $paymentGoals = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Payment goals retrieved successfully',
      'data' => new PaymentGoalCollection($paymentGoals)
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
      'target_amount' => 'required|integer|min:0',
      'amount' => 'required|integer|min:0',
      'progress_percent' => 'integer|min:0|max:100',
      'start_date' => 'required|date',
      'target_date' => 'required|date|after:start_date',
      'status_id' => ['required', 'integer', Rule::exists('payment_goal_statuses', 'id')],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    // Auto-calculate progress percentage if not provided
    if (!isset($validated['progress_percent']) || $validated['progress_percent'] === 0) {
      $target = $validated['target_amount'];
      $amount = $validated['amount'];
      $validated['progress_percent'] = $target > 0 ? round(($amount / $target) * 100, 2) : 0;
    }

    $paymentGoal = PaymentGoal::create($validated);

    return response()->json([
      'success' => true,
      'message' => 'Payment goal created successfully',
      'data' => new PaymentGoalResource($paymentGoal->load('status'))
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request, string $id): JsonResponse
  {
    $query = PaymentGoal::with('status');

    // Include trashed records if requested
    if ($request->has('with_trashed') && $request->boolean('with_trashed')) {
      $query->withTrashed();
    }

    $paymentGoal = $query->findOrFail($id);

    return response()->json([
      'success' => true,
      'message' => 'Payment goal retrieved successfully',
      'data' => new PaymentGoalResource($paymentGoal)
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id): JsonResponse
  {
    $paymentGoal = PaymentGoal::findOrFail($id);

    $validator = Validator::make($request->all(), [
      'name' => 'sometimes|string|max:255',
      'description' => 'nullable|string|max:1000',
      'target_amount' => 'sometimes|integer|min:0',
      'amount' => 'sometimes|integer|min:0',
      'progress_percent' => 'sometimes|integer|min:0|max:100',
      'start_date' => 'sometimes|date',
      'target_date' => 'sometimes|date|after:start_date',
      'status_id' => ['sometimes', 'integer', Rule::exists('payment_goal_statuses', 'id')],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    // Auto-calculate progress percentage if amount or target_amount changed
    if (isset($validated['amount']) || isset($validated['target_amount'])) {
      $target = $validated['target_amount'] ?? $paymentGoal->target_amount;
      $amount = $validated['amount'] ?? $paymentGoal->amount;
      $validated['progress_percent'] = $target > 0 ? round(($amount / $target) * 100, 2) : 0;
    }

    $paymentGoal->update($validated);

    return response()->json([
      'success' => true,
      'message' => 'Payment goal updated successfully',
      'data' => new PaymentGoalResource($paymentGoal->load('status'))
    ]);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id): JsonResponse
  {
    $paymentGoal = PaymentGoal::findOrFail($id);
    $paymentGoal->delete();

    return response()->json([
      'success' => true,
      'message' => 'Payment goal deleted successfully'
    ]);
  }

  /**
   * Force delete the specified resource from storage.
   */
  public function forceDestroy(string $id): JsonResponse
  {
    $paymentGoal = PaymentGoal::onlyTrashed()->findOrFail($id);
    $paymentGoal->forceDelete();

    return response()->json([
      'success' => true,
      'message' => 'Payment goal permanently deleted successfully'
    ]);
  }

  /**
   * Restore the specified resource from storage.
   */
  public function restore(string $id): JsonResponse
  {
    $paymentGoal = PaymentGoal::onlyTrashed()->findOrFail($id);
    $paymentGoal->restore();

    return response()->json([
      'success' => true,
      'message' => 'Payment goal restored successfully',
      'data' => new PaymentGoalResource($paymentGoal->load('status'))
    ]);
  }

  /**
   * Get payment goal statistics.
   */
  public function statistics(): JsonResponse
  {
    $stats = [
      'total_goals' => PaymentGoal::count(),
      'active_goals' => PaymentGoal::where('status_id', PaymentGoalStatus::ONGOING)->count(),
      'completed_goals' => PaymentGoal::where('status_id', PaymentGoalStatus::COMPLETED)->count(),
      'overdue_goals' => PaymentGoal::where('status_id', PaymentGoalStatus::OVERDUE)->count(),
      'total_target_amount' => PaymentGoal::sum('target_amount'),
      'total_current_amount' => PaymentGoal::sum('amount'),
      'average_progress' => PaymentGoal::avg('progress_percent'),
    ];

    $stats['achievement_rate'] = $stats['total_target_amount'] > 0
      ? round(($stats['total_current_amount'] / $stats['total_target_amount']) * 100, 2)
      : 0;

    return response()->json([
      'success' => true,
      'message' => 'Payment goal statistics retrieved successfully',
      'data' => $stats
    ]);
  }

  /**
   * Update progress for a payment goal.
   */
  public function updateProgress(Request $request, string $id): JsonResponse
  {
    $paymentGoal = PaymentGoal::findOrFail($id);

    $validator = Validator::make($request->all(), [
      'amount' => 'required|integer|min:0',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $target = $paymentGoal->target_amount;
    $amount = $validated['amount'];
    $progressPercent = $target > 0 ? round(($amount / $target) * 100, 2) : 0;

    $paymentGoal->update([
      'amount' => $amount,
      'progress_percent' => $progressPercent,
    ]);

    // Auto-update status based on progress
    if ($progressPercent >= 100) {
      $paymentGoal->update(['status_id' => PaymentGoalStatus::COMPLETED]);
    }

    return response()->json([
      'success' => true,
      'message' => 'Payment goal progress updated successfully',
      'data' => new PaymentGoalResource($paymentGoal->load('status'))
    ]);
  }

  /**
   * Get payment goals overview summary.
   */
  public function overview(): JsonResponse
  {
    $totalGoals = PaymentGoal::count();
    $completedGoals = PaymentGoal::where('status_id', PaymentGoalStatus::COMPLETED)->count();
    $successRate = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 2) : 0;

    return response()->json([
      'success' => true,
      'message' => 'Payment goals overview retrieved successfully',
      'data' => [
        'total_goals' => $totalGoals,
        'completed' => $completedGoals,
        'success_rate' => $successRate . '%'
      ]
    ]);
  }
}