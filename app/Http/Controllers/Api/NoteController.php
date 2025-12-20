<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NoteResource;
use App\Http\Resources\Api\NoteCollection;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
  /**
   * @OA\Get(
   *     path="/api/notes",
   *     summary="Get all notes with pagination",
   *     description="Retrieve a paginated list of notes for the authenticated user. Supports filtering by search, pinned status, archived status, and soft-deleted records.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer", default=1)),
   *     @OA\Parameter(name="per_page", in="query", description="Items per page (max 100)", @OA\Schema(type="integer", default=15)),
   *     @OA\Parameter(name="search", in="query", description="Search by title or content", @OA\Schema(type="string")),
   *     @OA\Parameter(name="is_pinned", in="query", description="Filter by pinned status", @OA\Schema(type="boolean")),
   *     @OA\Parameter(name="is_archived", in="query", description="Filter by archived status", @OA\Schema(type="boolean")),
   *     @OA\Parameter(name="with_trashed", in="query", description="Include soft-deleted records", @OA\Schema(type="boolean", default=false)),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'per_page' => 'nullable|integer|min:1|max:100',
      'search' => 'nullable|string|max:255',
      'is_pinned' => 'nullable|boolean',
      'is_archived' => 'nullable|boolean',
      'with_trashed' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $query = Note::where('user_id', auth()->user()->id)
      ->latest('updated_at');

    if ($request->has('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('content', 'like', "%{$search}%")
          ->orWhere('code', 'like', "%{$search}%");
      });
    }

    if ($request->has('is_pinned')) {
      $query->where('is_pinned', $request->boolean('is_pinned'));
    }

    if ($request->has('is_archived')) {
      $query->where('is_archived', $request->boolean('is_archived'));
    }

    if ($request->has('with_trashed') && $request->boolean('with_trashed')) {
      $query->withTrashed();
    }

    $perPage = $request->get('per_page', 15);
    $perPage = min($perPage, 100);

    $notes = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Notes retrieved successfully',
      'data' => new NoteCollection($notes)
    ]);
  }

  /**
   * @OA\Post(
   *     path="/api/notes",
   *     summary="Create new note",
   *     description="Create a new note for the authenticated user.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\RequestBody(required=true, @OA\JsonContent(
   *         required={"title"},
   *         @OA\Property(property="title", type="string", maxLength=255, description="Note title"),
   *         @OA\Property(property="content", type="string", nullable=true, description="Note content (supports markdown)"),
   *         @OA\Property(property="is_pinned", type="boolean", default=false, description="Pin note to top"),
   *         @OA\Property(property="is_archived", type="boolean", default=false, description="Archive note"),
   *         @OA\Property(property="request_view", type="boolean", default=false, description="If true, response includes view_url for admin panel link")
   *     )),
   *     @OA\Response(response=201, description="Note created successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title' => 'required|string|max:255',
      'content' => 'nullable|string',
      'is_pinned' => 'nullable|boolean',
      'is_archived' => 'nullable|boolean',
      'request_view' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $validated['user_id'] = auth()->user()->id;
    $validated['code'] = getCode('note');

    $note = Note::create($validated);
    $note->request_view = $validated['request_view'] ?? false;

    return response()->json([
      'success' => true,
      'message' => 'Note created successfully',
      'data' => new NoteResource($note)
    ], 201);
  }

  /**
   * @OA\Get(
   *     path="/api/notes/{note}",
   *     summary="Get specific note",
   *     description="Retrieve a specific note by ID.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="note", in="path", required=true, description="Note ID", @OA\Schema(type="integer")),
   *     @OA\Parameter(name="with_trashed", in="query", description="Include soft-deleted records", @OA\Schema(type="boolean", default=false)),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
  public function show(Request $request, Note $note): JsonResponse
  {
    if ($request->has('with_trashed') && $request->boolean('with_trashed')) {
      $note = Note::withTrashed()->findOrFail($note->id);
    }

    return response()->json([
      'success' => true,
      'message' => 'Note retrieved successfully',
      'data' => new NoteResource($note)
    ]);
  }

  /**
   * @OA\Get(
   *     path="/api/notes/{note:code}",
   *     summary="Get specific note by code",
   *     description="Retrieve a specific note by unique code.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="code", in="path", required=true, description="Unique note code", @OA\Schema(type="string")),
   *     @OA\Parameter(name="with_trashed", in="query", description="Include soft-deleted records", @OA\Schema(type="boolean", default=false)),
   *     @OA\Parameter(name="request_view", in="query", description="If true, response includes view_url for admin panel link", @OA\Schema(type="boolean", default=false)),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function showByCode(Request $request, string $code): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'with_trashed' => 'nullable|boolean',
      'request_view' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $query = Note::where('code', $code)
      ->where('user_id', auth()->user()->id);

    if ($validated['with_trashed'] ?? false) {
      $query->withTrashed();
    }

    $note = $query->first();

    if (!$note) {
      return response()->json([
        'success' => false,
        'message' => 'Note not found'
      ], 404);
    }

    $note->request_view = $validated['request_view'] ?? false;

    return response()->json([
      'success' => true,
      'message' => 'Note retrieved successfully',
      'data' => new NoteResource($note)
    ]);
  }

  /**
   * @OA\Put(
   *     path="/api/notes/{code}",
   *     summary="Update existing note",
   *     description="Update a specific note by unique code. All fields are optional.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="code", in="path", required=true, description="Unique note code", @OA\Schema(type="string")),
   *     @OA\RequestBody(required=true, @OA\JsonContent(
   *         @OA\Property(property="title", type="string", maxLength=255, description="Note title (optional)"),
   *         @OA\Property(property="content", type="string", description="Note content, supports markdown (optional)"),
   *         @OA\Property(property="is_pinned", type="boolean", description="Pin note to top (optional)"),
   *         @OA\Property(property="is_archived", type="boolean", description="Archive note (optional)"),
   *         @OA\Property(property="request_view", type="boolean", default=false, description="If true, response includes view_url for admin panel link (optional)")
   *     )),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
  public function update(Request $request, string $code): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'title' => 'sometimes|string|max:255',
      'content' => 'sometimes|string',
      'is_pinned' => 'sometimes|boolean',
      'is_archived' => 'sometimes|boolean',
      'request_view' => 'sometimes|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $note = Note::where('code', $code)
      ->where('user_id', auth()->user()->id)
      ->first();

    if (!$note) {
      return response()->json([
        'success' => false,
        'message' => 'Note not found'
      ], 404);
    }

    $note->update($validated);
    $note->request_view = $validated['request_view'] ?? false;

    return response()->json([
      'success' => true,
      'message' => 'Note updated successfully',
      'data' => new NoteResource($note)
    ]);
  }

  /**
   * @OA\Delete(
   *     path="/api/notes/{code}",
   *     summary="Delete note (soft delete)",
   *     description="Soft delete a specific note by unique code. The note can be restored later.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="code", in="path", required=true, description="Unique note code", @OA\Schema(type="string")),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
  public function destroy(string $code): JsonResponse
  {
    $note = Note::where('code', $code)
      ->where('user_id', auth()->user()->id)
      ->first();

    if (!$note) {
      return response()->json([
        'success' => false,
        'message' => 'Note not found'
      ], 404);
    }

    $note->delete();

    return response()->json([
      'success' => true,
      'message' => 'Note deleted successfully'
    ]);
  }

  /**
   * @OA\Delete(
   *     path="/api/notes/{note}/force",
   *     summary="Permanently delete note",
   *     description="Permanently delete a soft-deleted note. This action cannot be undone.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="note", in="path", required=true, description="Note ID", @OA\Schema(type="integer")),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
  public function forceDestroy(string $note): JsonResponse
  {
    $note = Note::onlyTrashed()
      ->where('user_id', auth()->user()->id)
      ->findOrFail($note);

    $note->forceDelete();

    return response()->json([
      'success' => true,
      'message' => 'Note permanently deleted successfully'
    ]);
  }

  /**
   * @OA\Post(
   *     path="/api/notes/{note}/restore",
   *     summary="Restore deleted note",
   *     description="Restore a soft-deleted note.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="note", in="path", required=true, description="Note ID", @OA\Schema(type="integer")),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
  public function restore(string $note): JsonResponse
  {
    $note = Note::onlyTrashed()
      ->where('user_id', auth()->user()->id)
      ->findOrFail($note);

    $note->restore();

    return response()->json([
      'success' => true,
      'message' => 'Note restored successfully',
      'data' => new NoteResource($note)
    ]);
  }

  /**
   * @OA\Patch(
   *     path="/api/notes/{note}/toggle-pin",
   *     summary="Toggle note pin status",
   *     description="Toggle the pinned status of a note.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="note", in="path", required=true, description="Note ID", @OA\Schema(type="integer")),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
  public function togglePin(Note $note): JsonResponse
  {
    $note->update(['is_pinned' => !$note->is_pinned]);

    return response()->json([
      'success' => true,
      'message' => $note->is_pinned ? 'Note pinned successfully' : 'Note unpinned successfully',
      'data' => new NoteResource($note)
    ]);
  }

  /**
   * @OA\Patch(
   *     path="/api/notes/{note}/toggle-archive",
   *     summary="Toggle note archive status",
   *     description="Toggle the archived status of a note.",
   *     tags={"Notes"},
   *     security={{"bearerAuth":{}}},
   *     @OA\Parameter(name="note", in="path", required=true, description="Note ID", @OA\Schema(type="integer")),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/UnauthenticatedResponse"))
   * )
   */
  public function toggleArchive(Note $note): JsonResponse
  {
    $note->update(['is_archived' => !$note->is_archived]);

    return response()->json([
      'success' => true,
      'message' => $note->is_archived ? 'Note archived successfully' : 'Note unarchived successfully',
      'data' => new NoteResource($note)
    ]);
  }
}
