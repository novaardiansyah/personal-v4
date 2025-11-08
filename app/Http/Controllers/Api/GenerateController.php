<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GenerateController extends Controller
{
  public function getCode(Request $request)
  {
    $validate = $request->validate([
      'alias' => 'required|string',
    ]);

    $alias = $validate['alias'];
    
    return response()->json(['success' => true, 'data' => getCode($alias)]);
  }
}
