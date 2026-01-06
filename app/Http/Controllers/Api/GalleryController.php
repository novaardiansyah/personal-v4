<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\GalleryCollection;
use App\Http\Resources\Api\GalleryResource;
use App\Models\Gallery;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class GalleryController extends Controller
{
  #[OA\Get(
    path: "/api/galleries",
    summary: "Get all galleries with pagination",
    description: "Retrieve a paginated list of galleries for the authenticated user. Supports filtering by search and soft-deleted records.",
    tags: ["Galleries"],
    security: [["bearerAuth" => []]],
    parameters: [
      new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer", default: 1)),
      new OA\Parameter(name: "per_page", in: "query", description: "Items per page (max 100)", schema: new OA\Schema(type: "integer", default: 15)),
      new OA\Parameter(name: "search", in: "query", description: "Search by file name or description", schema: new OA\Schema(type: "string")),
      new OA\Parameter(name: "with_trashed", in: "query", description: "Include soft-deleted records", schema: new OA\Schema(type: "boolean", default: false))
    ],
    responses: [
      new OA\Response(response: 200, description: "Success"),
      new OA\Response(response: 401, description: "Unauthenticated"),
      new OA\Response(response: 422, description: "Validation error")
    ]
  )]
  public function index(Request $request)
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

    $query = Gallery::where('user_id', Auth()->id())
      ->latest('updated_at');

    if ($request->has('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('file_name', 'like', "%{$search}%")
          ->orWhere('description', 'like', "%{$search}%");
      });
    }

    if ($request->has('with_trashed') && $request->boolean('with_trashed')) {
      $query->withTrashed();
    }

    $perPage = $request->get('per_page', 10);
    $perPage = min($perPage, 100);

    $galleries = $query->paginate($perPage);
    $result = (new GalleryCollection($galleries))->toArray($request);

    return response()->json([
      'success' => true,
      'message' => 'Galleries retrieved successfully',
      'data' => $result['data'] ?? null,
      'meta' => $result['meta'] ?? null,
    ]);
  }

  #[OA\Get(
    path: "/api/galleries/{gallery}",
    summary: "Get specific gallery",
    description: "Retrieve a specific gallery by ID.",
    tags: ["Galleries"],
    security: [["bearerAuth" => []]],
    parameters: [
      new OA\Parameter(name: "gallery", in: "path", required: true, description: "Gallery ID", schema: new OA\Schema(type: "integer"))
    ],
    responses: [
      new OA\Response(response: 200, description: "Success"),
      new OA\Response(response: 404, description: "Not found"),
      new OA\Response(response: 401, description: "Unauthenticated")
    ]
  )]
  public function show(Request $request, Gallery $gallery)
  {
    return response()->json([
      'success' => true,
      'message' => 'Gallery retrieved successfully',
      'data' => new GalleryResource($gallery),
    ]);
  }

  #[OA\Post(
    path: "/api/galleries",
    summary: "Upload gallery images",
    description: "Upload one or more images to the gallery using base64 encoding.",
    tags: ["Galleries"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        required: ["base64_array"],
        properties: [
          new OA\Property(property: "base64_array", type: "array", items: new OA\Items(type: "string"), description: "Array of base64 encoded images"),
          new OA\Property(property: "is_optimized", type: "boolean", default: false, description: "If true, skip optimization process")
        ]
      )
    ),
    responses: [
      new OA\Response(response: 200, description: "Images uploaded successfully"),
      new OA\Response(response: 413, description: "Payload too large"),
      new OA\Response(response: 401, description: "Unauthenticated"),
      new OA\Response(response: 422, description: "Validation error")
    ]
  )]
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'base64_array' => 'required|array',
      'base64_array.*' => 'string',
      'is_optimized' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $base64Array = $validated['base64_array'];
    $count = count($base64Array);

    if ($count > 5) {
      return response()->json([
        'success' => false,
        'message' => 'Maximum 5 images allowed',
        'errors' => ['base64_array' => 'Maximum 5 images allowed'],
      ], 422);
    }

    $is_optimized = $validated['is_optimized'] ?? false;
    $uploaded = [];
    $errors = [];

    foreach ($base64Array as $index => $image) {
      $this->processBase64Upload($image, $uploaded, $errors, $index, $is_optimized);
    }

    if (!empty($uploaded)) {
      foreach ($uploaded as $images) {
        $gallery = Gallery::create([
          'file_path' => $images['original'],
          'has_optimized' => true,
          'user_id' => Auth()->id(),
        ]);

        foreach ($images as $key => $image) {
          if ($key == 'original')
            continue;

          $rep = $gallery->replicate();

          $rep->file_path = $image;
          $rep->has_optimized = false;
          $rep->subject_id = $gallery->id;
          $rep->subject_type = Gallery::class;

          $rep->save();
        }
      }
    }

    if (!empty($errors)) {
      return response()->json([
        'success' => false,
        'message' => 'Some images failed to upload',
        'errors' => $errors,
      ], 422);
    }

    return response()->json([
      'success' => true,
      'message' => 'Images uploaded successfully',
      'data' => $uploaded,
    ]);
  }

  private function processBase64Upload(string $image, array &$uploaded, array &$errors, int $index, bool $is_optimized = false): void
  {
    $path = 'images/gallery';

    try {
      $upload = processBase64Image($image, $path);
      $optimized = [];

      if ($upload) {
        if (!$is_optimized) {
          $optimized = uploadAndOptimize($upload, 'public', $path);
        } else {
          $optimized = ['original' => $upload];
        }

        $uploaded[] = $optimized;
      } else {
        $errors['base64_array'] = ['Failed to upload image'];
      }
    } catch (Exception $e) {
      $errors['base64_array'] = ['Failed to upload image: ' . $e->getMessage()];
    }
  }

  #[OA\Delete(
    path: "/api/galleries/{gallery}",
    summary: "Delete gallery (soft delete)",
    description: "Soft delete a specific gallery by ID. The gallery can be restored later.",
    tags: ["Galleries"],
    security: [["bearerAuth" => []]],
    parameters: [
      new OA\Parameter(name: "gallery", in: "path", required: true, description: "Gallery ID", schema: new OA\Schema(type: "integer"))
    ],
    responses: [
      new OA\Response(response: 200, description: "Success"),
      new OA\Response(response: 404, description: "Not found"),
      new OA\Response(response: 401, description: "Unauthenticated")
    ]
  )]
  public function destroy(Request $request, Gallery $gallery): JsonResponse
  {
    $gallery->delete();

    return response()->json([
      'success' => true,
      'message' => 'Gallery deleted successfully',
    ]);
  }

  #[OA\Delete(
    path: "/api/galleries/{gallery}/force",
    summary: "Permanently delete gallery",
    description: "Permanently delete a gallery. This action cannot be undone.",
    tags: ["Galleries"],
    security: [["bearerAuth" => []]],
    parameters: [
      new OA\Parameter(name: "gallery", in: "path", required: true, description: "Gallery ID", schema: new OA\Schema(type: "integer"))
    ],
    responses: [
      new OA\Response(response: 200, description: "Success"),
      new OA\Response(response: 404, description: "Not found"),
      new OA\Response(response: 401, description: "Unauthenticated")
    ]
  )]
  public function forceDelete(Request $request, string $id): JsonResponse
  {
    $gallery = Gallery::withTrashed()->findOrFail($id);
    $gallery->forceDelete();

    return response()->json([
      'success' => true,
      'message' => 'Gallery deleted successfully',
    ]);
  }
}
