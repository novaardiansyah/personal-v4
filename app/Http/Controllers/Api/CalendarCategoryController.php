<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CalendarCategoryResource;
use App\Http\Resources\Api\CalendarCategoryCollection;
use App\Models\CalendarCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class CalendarCategoryController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $categories = CalendarCategory::withCount('events')
      ->get();

    return response()->json([
      'success' => true,
      'message' => 'Calendar categories retrieved successfully',
      'data'    => new CalendarCategoryCollection($categories)
    ]);
  }

  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'name'       => 'required|string|max:255',
      'color'      => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
      'is_default' => 'nullable|boolean',
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
    $validated['color']   = $validated['color'] ?? '#3B82F6';

    $category = CalendarCategory::create($validated);

    return response()->json([
      'success' => true,
      'message' => 'Calendar category created successfully',
      'data'    => new CalendarCategoryResource($category)
    ], 201);
  }

  public function update(Request $request, string $id): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'name'       => 'sometimes|string|max:255',
      'color'      => 'sometimes|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
      'is_default' => 'sometimes|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors()
      ], 422);
    }

    $category = CalendarCategory::findOrFail($id);
    $category->update($validator->validated());

    return response()->json([
      'success' => true,
      'message' => 'Calendar category updated successfully',
      'data'    => new CalendarCategoryResource($category)
    ]);
  }

  public function destroy(string $id): JsonResponse
  {
    $category = CalendarCategory::findOrFail($id);

    if ($category->events()->count() > 0) {
      return response()->json([
        'success' => false,
        'message' => 'Cannot delete category with existing events'
      ], 422);
    }

    $category->delete();

    return response()->json([
      'success' => true,
      'message' => 'Calendar category deleted successfully'
    ]);
  }
}
