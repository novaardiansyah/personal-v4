<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
  /**
   * Get all galleries
   */
  public function index()
  {
    $gallery = Gallery::with(['tag:id,name,slug'])->select(['id', 'image', 'tag_id', 'url'])
      ->where('is_publish', true)
      ->inRandomOrder()
      ->limit(12)
      ->get();

    foreach ($gallery as $key => $item) {
      $gallery[$key]['image'] = asset('storage/' . $item['image']);
    }

    return response()->json([
      'status' => true,
      'data' => $gallery
    ]);
  }

  /**
   * Get gallery by ID
   */
  public function show($id)
  {
    $gallery = Gallery::with('tag:id,name,slug')->find($id);

    if (!$gallery) {
      return response()->json([
        'success' => false,
        'message' => 'Gallery not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data' => $gallery,
      'message' => 'Gallery retrieved successfully'
    ]);
  }

  /**
   * Get galleries by tag
   */
  public function getByTag($tagId)
  {
    $galleries = Gallery::query()
      ->with('tag:id,name,slug')
      ->where('tag_id', $tagId)
      ->where('is_publish', true)
      ->orderBy('updated_at', 'desc')
      ->get([
        'id',
        'image',
        'description',
        'url',
        'tag_id',
        'is_publish',
        'created_at',
        'updated_at'
      ]);

    return response()->json([
      'success' => true,
      'data' => $galleries,
      'message' => 'Galleries by tag retrieved successfully'
    ]);
  }
}
