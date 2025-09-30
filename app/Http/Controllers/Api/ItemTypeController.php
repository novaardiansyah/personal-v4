<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ItemType;

class ItemTypeController extends Controller
{
  /**
   * Get all item types
   */
  public function index(): JsonResponse
  {
    $itemTypes = ItemType::orderBy('name')->get();

    return response()->json([
      'success' => true,
      'message' => 'Item types retrieved successfully',
      'data' => $itemTypes
    ]);
  }
}