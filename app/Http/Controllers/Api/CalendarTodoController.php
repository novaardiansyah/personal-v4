<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CalendarTodoResource;
use App\Http\Resources\Api\CalendarTodoCollection;
use App\Models\CalendarTodo;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class CalendarTodoController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'page'       => 'nullable|integer|min:1',
      'limit'      => 'nullable|integer|min:1|max:100',
      'event_id'   => 'nullable|integer|exists:calendar_events,id',
      'priority'   => 'nullable|string|in:low,medium,high',
      'is_completed' => 'nullable|boolean',
      'due_from'   => 'nullable|date',
      'due_to'     => 'nullable|date',
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
    $eventId   = $validated['event_id'] ?? null;
    $priority  = $validated['priority'] ?? null;
    $isCompleted = $validated['is_completed'] ?? null;
    $dueFrom   = $validated['due_from'] ?? null;
    $dueTo     = $validated['due_to'] ?? null;
    $withTrashed = $validated['with_trashed'] ?? false;

    $query = CalendarTodo::with(['event.category'])
      ->where('user_id', Auth::id());

    if ($withTrashed) {
      $query->withTrashed();
    }

    if ($eventId) {
      $query->where('event_id', $eventId);
    }

    if ($priority) {
      $query->where('priority', $priority);
    }

    if ($isCompleted !== null) {
      if ($isCompleted) {
        $query->whereNotNull('completed_at');
      } else {
        $query->whereNull('completed_at');
      }
    }

    if ($dueFrom) {
      $query->whereDate('due_at', '>=', $dueFrom);
    }

    if ($dueTo) {
      $query->whereDate('due_at', '<=', $dueTo);
    }

    $todos = $query
      ->orderBy('sort_order', 'asc')
      ->orderBy('due_at', 'asc')
      ->paginate(min($limit, 100));

    return response()->json([
      'success' => true,
      'message' => 'Calendar todos retrieved successfully',
      'data'    => new CalendarTodoCollection($todos),
      'pagination' => [
        'current_page' => $todos->currentPage(),
        'from'         => $todos->firstItem(),
        'last_page'    => $todos->lastPage(),
        'per_page'     => $todos->perPage(),
        'to'           => $todos->lastItem(),
        'total'        => $todos->total(),
      ]
    ]);
  }

  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title'       => 'required|string|max:255',
      'description' => 'nullable|string',
      'event_id'    => 'nullable|integer|exists:calendar_events,id',
      'priority'    => 'nullable|string|in:low,medium,high',
      'due_at'      => 'nullable|date',
      'sort_order'  => 'nullable|integer|min:0',
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
    $validated['code']    = getCode('calendar_todo');

    if ($validated['event_id'] ?? null) {
      $event = CalendarEvent::where('id', $validated['event_id'])
        ->where('user_id', Auth::id())
        ->first();

      if (!$event) {
        return response()->json([
          'success' => false,
          'message' => 'Calendar event not found'
        ], 404);
      }
    }

    $todo = CalendarTodo::create($validated);
    $todo->load(['event.category']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar todo created successfully',
      'data'    => new CalendarTodoResource($todo)
    ], 201);
  }

  public function show(Request $request, CalendarTodo $todo): JsonResponse
  {
    if ($request->has('with_trashed') && $request->boolean('with_trashed')) {
      $todo = CalendarTodo::withTrashed()->findOrFail($todo->id);
    }

    $todo->load(['event.category']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar todo retrieved successfully',
      'data'    => new CalendarTodoResource($todo)
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

    $query = CalendarTodo::where('code', $code)
      ->where('user_id', Auth::id());

    if ($validated['with_trashed'] ?? false) {
      $query->withTrashed();
    }

    $todo = $query->first();

    if (!$todo) {
      return response()->json([
        'success' => false,
        'message' => 'Calendar todo not found'
      ], 404);
    }

    $todo->load(['event.category']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar todo retrieved successfully',
      'data'    => new CalendarTodoResource($todo)
    ]);
  }

  public function update(Request $request, string $code): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title'       => 'sometimes|string|max:255',
      'description' => 'sometimes|string',
      'event_id'    => 'sometimes|integer|exists:calendar_events,id',
      'priority'    => 'sometimes|string|in:low,medium,high',
      'due_at'      => 'sometimes|date',
      'sort_order'  => 'sometimes|integer|min:0',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $todo = CalendarTodo::where('code', $code)
      ->where('user_id', Auth::id())
      ->first();

    if (!$todo) {
      return response()->json([
        'success' => false,
        'message' => 'Calendar todo not found'
      ], 404);
    }

    if ($validated['event_id'] ?? null) {
      $event = CalendarEvent::where('id', $validated['event_id'])
        ->where('user_id', Auth::id())
        ->first();

      if (!$event) {
        return response()->json([
          'success' => false,
          'message' => 'Calendar event not found'
        ], 404);
      }
    }

    $todo->update($validated);
    $todo->load(['event.category']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar todo updated successfully',
      'data'    => new CalendarTodoResource($todo)
    ]);
  }

  public function destroy(string $code): JsonResponse
  {
    $todo = CalendarTodo::where('code', $code)
      ->where('user_id', Auth::id())
      ->first();

    if (!$todo) {
      return response()->json([
        'success' => false,
        'message' => 'Calendar todo not found'
      ], 404);
    }

    $todo->delete();

    return response()->json([
      'success' => true,
      'message' => 'Calendar todo deleted successfully'
    ]);
  }

  public function toggle(string $id): JsonResponse
  {
    $todo = CalendarTodo::where('user_id', Auth::id())
      ->FindOrFail($id);

    if ($todo->completed_at) {
      $todo->update(['completed_at' => null]);
      $message = 'Calendar todo marked as incomplete';
    } else {
      $todo->update(['completed_at' => now()]);
      $message = 'Calendar todo completed successfully';
    }

    $todo->load(['event.category']);

    return response()->json([
      'success' => true,
      'message' => $message,
      'data'    => new CalendarTodoResource($todo)
    ]);
  }

  public function forceDestroy(string $id): JsonResponse
  {
    $todo = CalendarTodo::onlyTrashed()
      ->where('user_id', Auth::id())
      ->findOrFail($id);

    $todo->forceDelete();

    return response()->json([
      'success' => true,
      'message' => 'Calendar todo permanently deleted successfully'
    ]);
  }

  public function restore(string $id): JsonResponse
  {
    $todo = CalendarTodo::onlyTrashed()
      ->where('user_id', Auth::id())
      ->findOrFail($id);

    $todo->restore();
    $todo->load(['event.category']);

    return response()->json([
      'success' => true,
      'message' => 'Calendar todo restored successfully',
      'data'    => new CalendarTodoResource($todo)
    ]);
  }
}
