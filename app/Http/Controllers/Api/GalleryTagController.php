<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryTag;
use Illuminate\Http\Request;

class GalleryTagController extends Controller
{
    /**
     * Get all gallery tags
     */
    public function index()
    {
        $tags = GalleryTag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'data' => $tags,
            'message' => 'Gallery tags retrieved successfully'
        ]);
    }

    /**
     * Get gallery tag by ID
     */
    public function show($id)
    {
        $tag = GalleryTag::find($id);

        if (!$tag) {
            return response()->json([
                'success' => false,
                'message' => 'Gallery tag not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tag,
            'message' => 'Gallery tag retrieved successfully'
        ]);
    }
}