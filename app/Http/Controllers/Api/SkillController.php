<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * Get all skills
     */
    public function index()
    {
        $skills = Skill::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'percentage']);

        return response()->json([
            'success' => true,
            'data' => $skills,
            'message' => 'Skills retrieved successfully'
        ]);
    }

    /**
     * Get skill by ID
     */
    public function show($id)
    {
        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json([
                'success' => false,
                'message' => 'Skill not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $skill,
            'message' => 'Skill retrieved successfully'
        ]);
    }
}