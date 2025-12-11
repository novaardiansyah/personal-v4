<?php

namespace App\Http\Controllers\Api;

use App\Enums\BlogPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogPostListResource;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogPostController extends Controller
{
  /**
   * Get all blog posts with pagination
   */
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'page' => 'nullable|integer|min:1',
      'limit' => 'nullable|integer|min:1',
      'search' => 'nullable|string|max:255',
      'status' => 'nullable|string|in:draft,published,scheduled,archived',
      'category_id' => 'nullable|integer|exists:blog_categories,id',
      'author_id' => 'nullable|integer|exists:users,id',
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
    $status = $validated['status'] ?? null;
    $categoryId = $validated['category_id'] ?? null;
    $authorId = $validated['author_id'] ?? null;

    $posts = BlogPost::with(['author', 'category']);

    if ($status) {
      $posts->where('status', $status);
    }

    if ($categoryId) {
      $posts->where('category_id', $categoryId);
    }

    if ($authorId) {
      $posts->where('author_id', $authorId);
    }

    if ($search) {
      $posts->where(function ($query) use ($search) {
        $query->where('title', 'like', '%' . $search . '%')
          ->orWhere('slug', 'like', '%' . $search . '%')
          ->orWhere('excerpt', 'like', '%' . $search . '%');
      });
    }

    $posts = $posts
      ->orderBy('display_order')
      ->orderByDesc('published_at')
      ->orderByDesc('created_at')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => BlogPostListResource::collection($posts),
      'pagination' => [
        'current_page' => $posts->currentPage(),
        'from' => $posts->firstItem(),
        'last_page' => $posts->lastPage(),
        'per_page' => $posts->perPage(),
        'to' => $posts->lastItem(),
        'total' => $posts->total(),
      ]
    ]);
  }

  /**
   * Get published blog posts (public access)
   */
  public function published(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'page' => 'nullable|integer|min:1',
      'limit' => 'nullable|integer|min:1',
      'search' => 'nullable|string|max:255',
      'category_id' => 'nullable|integer|exists:blog_categories,id',
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
    $categoryId = $validated['category_id'] ?? null;

    $posts = BlogPost::with(['author', 'category'])
      ->where('status', BlogPostStatus::Published);

    if ($categoryId) {
      $posts->where('category_id', $categoryId);
    }

    if ($search) {
      $posts->where(function ($query) use ($search) {
        $query->where('title', 'like', '%' . $search . '%')
          ->orWhere('slug', 'like', '%' . $search . '%')
          ->orWhere('excerpt', 'like', '%' . $search . '%');
      });
    }

    $posts = $posts
      ->orderBy('display_order')
      ->orderByDesc('published_at')
      ->paginate($limit);

    return response()->json([
      'success' => true,
      'data' => BlogPostListResource::collection($posts),
      'pagination' => [
        'current_page' => $posts->currentPage(),
        'from' => $posts->firstItem(),
        'last_page' => $posts->lastPage(),
        'per_page' => $posts->perPage(),
        'to' => $posts->lastItem(),
        'total' => $posts->total(),
      ]
    ]);
  }

  /**
   * Get blog post by slug (route model binding)
   */
  public function showBySlug(BlogPost $blogPost): JsonResponse
  {
    $blogPost->load(['author', 'category']);
    $blogPost->increment('view_count');

    return response()->json([
      'success' => true,
      'data'    => new BlogPostResource($blogPost),
      'message' => 'Blog post retrieved successfully'
    ]);
  }
}
