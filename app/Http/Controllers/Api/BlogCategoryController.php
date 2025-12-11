<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogCategoryController extends Controller
{
  /**
   * Get all blog categories with pagination
   */
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'page' => 'nullable|integer|min:1',
      'limit' => 'nullable|integer|min:1',
      'search' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();
    $limit = $validated['limit'] ?? 10;
    $search = $validated['search'] ?? null;

    $categories = BlogCategory::query();

    if ($search) {
      $categories->where(function ($query) use ($search) {
        $query->where('name', 'like', '%' . $search . '%')
          ->orWhere('slug', 'like', '%' . $search . '%')
          ->orWhere('description', 'like', '%' . $search . '%');
      });
    }

    $categories = $categories
      ->orderBy('display_order')
      ->orderBy('name')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => BlogCategoryResource::collection($categories),
      'pagination' => [
        'current_page' => $categories->currentPage(),
        'from' => $categories->firstItem(),
        'last_page' => $categories->lastPage(),
        'per_page' => $categories->perPage(),
        'to' => $categories->lastItem(),
        'total' => $categories->total(),
      ]
    ]);
  }

  /**
   * Get blog category by ID
   */
  public function show($id): JsonResponse
  {
    $category = BlogCategory::find($id);

    if (!$category) {
      return response()->json([
        'success' => false,
        'message' => 'Blog category not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => new BlogCategoryResource($category),
      'message' => 'Blog category retrieved successfully'
    ]);
  }
}
