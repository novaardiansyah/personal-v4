<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CalendarEventResource;
use App\Http\Resources\Api\CalendarEventCollection;
use App\Models\CalendarEvent;
use App\Models\CalendarCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class CalendarEventController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'page'       => 'nullable|integer|min:1',
      'limit'      => 'nullable|integer|min:1|max:100',
      'month'      => 'nullable|date_format:Y-m',
      'category'   => 'nullable|integer|exists:calendar_categories,id',
      'source'     => 'nullable|string|in:payment,debt,note,manual',
      'date_from'  => 'nullable|date',
      'date_to'    => 'nullable|date',
      'with_trashed' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $limit     = $validated['limit'] ?? 15;
    $month     = $validated['month'] ?? null;
    $category  = $validated['category'] ?? null;
    $source    = $validated['source'] ?? null;
    $dateFrom  = $validated['date_from'] ?? null;
    $dateTo    = $validated['date_to'] ?? null;
    $withTrashed = $validated['with_trashed'] ?? false;

    $query = CalendarEvent::with(['category', 'reminders', 'todos'])
      ->where('user_id', Auth::id());

    if ($withTrashed) {
      $query->withTrashed();
    }

    if ($month) {
      $startOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
      $endOfMonth   = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
      $query->whereBetween('start_at', [$startOfMonth, $endOfMonth]);
    }

    if ($category) {
      $query->where('category_id', $category);
    }

    if ($source) {
      $query->where('source_type', $source);
    }

    if ($dateFrom) {
      $query->whereDate('start_at', '>=', $dateFrom);
    }

    if ($dateTo) {
      $query->whereDate('start_at', '<=', $dateTo);
    }

    $events = $query
      ->orderBy('start_at', 'asc')
      ->paginate(min($limit, 100));

    return response()->json([
      'success' => true,
      'message' => 'Calendar events retrieved successfully',
      'data'    => new CalendarEventCollection($events),
      'pagination' => [
        'current_page' => $events->currentPage(),
        'from'         => $events->firstItem(),
        'last_page'    => $events->lastPage(),
        'per_page'     => $events->perPage(),
        'to'           => $events->lastItem(),
        'total'        => $events->total(),
      ]
    ]);
  }

  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title'               => 'required|string|max:255',
      'description'         => 'nullable|string',
      'location'            => 'nullable|string|max:255',
      'start_at'            => 'required|date',
      'end_at'              => 'nullable|date|after_or_equal:start_at',
      'is_all_day'          => 'nullable|boolean',
      'category_id'         => 'nullable|integer|exists:calendar_categories,id',
      'color'               => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
      'recurrence_type'     => 'nullable|string|in:daily,weekly,monthly,yearly',
      'recurrence_interval' => 'nullable|integer|min:1',
      'recurrence_end_at'   => 'nullable|date|after:start_at',
      'metadata'            => 'nullable|json',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $validated['user_id'] = Auth::id();
    $validated['code']    = getCode('calendar_event');

    if (empty($validated['recurrence_type'])) {
      $validated['recurrence_type']      = null;
      $validated['recurrence_interval']  = null;
      $validated['recurrence_end_at']    = null;
    }

    $event = CalendarEvent::create($validated);
    $event->load(['category', 'reminders', 'todos']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar event created successfully',
      'data'    => new CalendarEventResource($event)
    ], 201);
  }

  public function show(Request $request, CalendarEvent $event): JsonResponse
  {
    if ($request->has('with_trashed') && $request->boolean('with_trashed')) {
      $event = CalendarEvent::withTrashed()->findOrFail($event->id);
    }

    $event->load(['category', 'reminders', 'todos']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar event retrieved successfully',
      'data'    => new CalendarEventResource($event)
    ]);
  }

  public function showByCode(Request $request, string $code): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'with_trashed' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $query = CalendarEvent::where('code', $code)
      ->where('user_id', Auth::id());

    if ($validated['with_trashed'] ?? false) {
      $query->withTrashed();
    }

    $event = $query->first();

    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => 'Calendar event not found'
      ], 404);
    }

    $event->load(['category', 'reminders', 'todos']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar event retrieved successfully',
      'data'    => new CalendarEventResource($event)
    ]);
  }

  public function update(Request $request, string $code): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title'               => 'sometimes|string|max:255',
      'description'         => 'sometimes|string',
      'location'            => 'sometimes|string|max:255',
      'start_at'            => 'sometimes|date',
      'end_at'              => 'sometimes|date|after_or_equal:start_at',
      'is_all_day'          => 'sometimes|boolean',
      'category_id'         => 'sometimes|integer|exists:calendar_categories,id',
      'color'               => 'sometimes|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
      'recurrence_type'     => 'sometimes|string|in:daily,weekly,monthly,yearly',
      'recurrence_interval' => 'sometimes|integer|min:1',
      'recurrence_end_at'   => 'sometimes|date|after:start_at',
      'metadata'            => 'sometimes|json',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $event = CalendarEvent::where('code', $code)
      ->where('user_id', Auth::id())
      ->first();

    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => 'Calendar event not found'
      ], 404);
    }

    if (array_key_exists('recurrence_type', $validated) && empty($validated['recurrence_type'])) {
      $validated['recurrence_type']      = null;
      $validated['recurrence_interval']  = null;
      $validated['recurrence_end_at']    = null;
    }

    $event->update($validated);
    $event->load(['category', 'reminders', 'todos']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar event updated successfully',
      'data'    => new CalendarEventResource($event)
    ]);
  }

  public function destroy(string $code): JsonResponse
  {
    $event = CalendarEvent::where('code', $code)
      ->where('user_id', Auth::id())
      ->first();

    if (!$event) {
      return response()->json([
        'success' => false,
        'message' => 'Calendar event not found'
      ], 404);
    }

    $event->delete();

    return response()->json([
      'success' => true,
      'message' => 'Calendar event deleted successfully'
    ]);
  }

  public function forceDestroy(string $id): JsonResponse
  {
    $event = CalendarEvent::onlyTrashed()
      ->where('user_id', Auth::id())
      ->findOrFail($id);

    $event->forceDelete();

    return response()->json([
      'success' => true,
      'message' => 'Calendar event permanently deleted successfully'
    ]);
  }

  public function restore(string $id): JsonResponse
  {
    $event = CalendarEvent::onlyTrashed()
      ->where('user_id', Auth::id())
      ->findOrFail($id);

    $event->restore();
    $event->load(['category', 'reminders', 'todos']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar event restored successfully',
      'data'    => new CalendarEventResource($event)
    ]);
  }

  public function duplicate(string $id): JsonResponse
  {
    $original = CalendarEvent::where('user_id', Auth::id())
      ->FindOrFail($id);

    $duplicated = $original->replicate();
    $duplicated->code     = getCode('calendar_event');
    $duplicated->metadata = null;
    $duplicated->save();
    $duplicated->load(['category', 'reminders', 'todos']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar event duplicated successfully',
      'data'    => new CalendarEventResource($duplicated)
    ]);
  }

  public function upcoming(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'days' => 'nullable|integer|in:7,14,30',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $days   = $request->input('days', 7);
    $now    = now();
    $end    = now()->addDays($days);

    $events = CalendarEvent::with(['category'])
      ->where('user_id', Auth::id())
      ->whereBetween('start_at', [$now, $end])
      ->orderBy('start_at', 'asc')
      ->get();

    $todos = \App\Models\CalendarTodo::with(['event.category'])
      ->where('user_id', Auth::id())
      ->whereNull('completed_at')
      ->whereBetween('due_at', [$now, $end])
      ->orderBy('due_at', 'asc')
      ->get();

    return response()->json([
      'success' => true,
      'message' => 'Upcoming events and todos retrieved successfully',
      'data'    => [
        'events' => new CalendarEventCollection($events),
        'todos'  => new \App\Http\Resources\Api\CalendarTodoCollection($todos),
      ]
    ]);
  }
}
