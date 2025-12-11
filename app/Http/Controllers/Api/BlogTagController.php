<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogTagResource;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogTagController extends Controller
{
  /**
   * Get all blog tags with pagination
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

    $tags = BlogTag::query();

    if ($search) {
      $tags->where(function ($query) use ($search) {
        $query->where('name', 'like', '%' . $search . '%')
          ->orWhere('slug', 'like', '%' . $search . '%')
          ->orWhere('description', 'like', '%' . $search . '%');
      });
    }

    $tags = $tags
      ->orderBy('display_order')
      ->orderBy('name')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => BlogTagResource::collection($tags),
      'pagination' => [
        'current_page' => $tags->currentPage(),
        'from' => $tags->firstItem(),
        'last_page' => $tags->lastPage(),
        'per_page' => $tags->perPage(),
        'to' => $tags->lastItem(),
        'total' => $tags->total(),
      ]
    ]);
  }

  /**
   * Get blog tag by ID
   */
  public function show($id): JsonResponse
  {
    $tag = BlogTag::find($id);

    if (!$tag) {
      return response()->json([
        'success' => false,
        'message' => 'Blog tag not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => new BlogTagResource($tag),
      'message' => 'Blog tag retrieved successfully'
    ]);
  }
}
