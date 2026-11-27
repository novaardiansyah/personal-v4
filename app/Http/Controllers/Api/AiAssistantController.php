<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAssistantContext;
use App\Models\AiAssistantMessage;
use App\Models\AiAssistantSession;
use App\Services\AiAssistant\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AiAssistantController extends Controller
{
  public function contexts(): JsonResponse
  {
    $contexts = AiAssistantContext::active()
      ->ordered()
      ->get(['id', 'uuid', 'name', 'description', 'default_model', 'starter_prompts']);

    return response()->json([
      'success' => true,
      'data'    => $contexts,
    ]);
  }

  public function sessions(Request $request): JsonResponse
  {
    $perPage  = min((int) $request->input('per_page', 15), 50);
    $sessions = AiAssistantSession::forUser((int) auth()->id())
      ->with('context:id,uuid,name')
      ->orderByDesc('is_pinned')
      ->latest('last_interacted_at')
      ->latest('updated_at')
      ->paginate($perPage);

    return response()->json([
      'success' => true,
      'data'    => $sessions,
    ]);
  }

  public function createSession(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title'          => 'nullable|string|max:255',
      'context_id'     => 'nullable|integer|exists:ai_assistant_contexts,id',
      'model_override' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $context = app(AiAssistantService::class)->resolveContext(
      $request->filled('context_id') ? (int) $request->input('context_id') : null
    );

    $session = AiAssistantSession::create([
      'user_id'            => (int) auth()->id(),
      'context_id'         => $context?->id,
      'uuid'               => uuid7(),
      'title'              => $request->input('title') ?: 'New Conversation',
      'model_override'     => $request->input('model_override'),
      'last_interacted_at' => now(),
    ]);

    return response()->json([
      'success' => true,
      'data'    => $session,
    ], 201);
  }

  public function messages(Request $request, string $uuid): JsonResponse
  {
    $session = AiAssistantSession::forUser((int) auth()->id())
      ->where('uuid', $uuid)
      ->first();

    if (!$session) {
      return response()->json([
        'success' => false,
        'message' => 'Session not found.',
      ], 404);
    }

    $perPage  = min((int) $request->input('per_page', 30), 100);
    $messages = AiAssistantMessage::forSession($session->id)
      ->whereIn('role', ['user', 'assistant'])
      ->whereNotNull('content')
      ->latest('id')
      ->paginate($perPage);

    return response()->json([
      'success' => true,
      'data'    => $messages,
    ]);
  }

  public function sendMessage(Request $request, string $uuid): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'message'    => 'required|string|max:10000',
      'context_id' => 'nullable|integer|exists:ai_assistant_contexts,id',
      'model'      => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $userId  = (int) auth()->id();
    $session = AiAssistantSession::forUser($userId)->where('uuid', $uuid)->first();

    if (!$session) {
      return response()->json([
        'success' => false,
        'message' => 'Session not found.',
      ], 404);
    }

    $result = app(AiAssistantService::class)->sendMessage(
      $session,
      $userId,
      (string) $request->input('message'),
      [
        'context_id' => $request->input('context_id'),
        'model'      => $request->input('model'),
      ]
    );

    return response()->json([
      'success' => ($result['status'] ?? '') === 'completed',
      'data'    => $result,
    ], ($result['status'] ?? '') === 'completed' ? 200 : 503);
  }

  public function confirmAction(Request $request, string $uuid, string $actionId): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'message_uuid' => 'required|string',
      'approved'     => 'required|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $userId  = (int) auth()->id();
    $session = AiAssistantSession::forUser($userId)->where('uuid', $uuid)->first();

    if (!$session) {
      return response()->json([
        'success' => false,
        'message' => 'Session not found.',
      ], 404);
    }

    $result = app(AiAssistantService::class)->confirmPendingAction(
      $session,
      $userId,
      (string) $request->input('message_uuid'),
      $actionId,
      $request->boolean('approved')
    );

    return response()->json([
      'success' => (bool) ($result['status'] ?? false),
      'data'    => $result,
    ], ($result['status'] ?? false) ? 200 : 422);
  }

  public function deleteSession(string $uuid): JsonResponse
  {
    $userId  = (int) auth()->id();
    $session = AiAssistantSession::forUser($userId)->where('uuid', $uuid)->first();

    if (!$session) {
      return response()->json([
        'success' => false,
        'message' => 'Session not found.',
      ], 404);
    }

    AiAssistantMessage::forSession($session->id)->delete();
    $session->delete();

    return response()->json([
      'success' => true,
      'message' => 'Session deleted.',
    ]);
  }
}
