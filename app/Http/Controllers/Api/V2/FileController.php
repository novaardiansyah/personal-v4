<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Enums\FileType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\FileCollection;
use App\Http\Resources\Api\V2\FileResource;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'per_page'     => 'nullable|integer|min:1|max:100',
      'search'       => 'nullable|string|max:255',
      'with_trashed' => 'nullable|boolean',
      'sort_by'      => 'nullable|string|in:id,created_at,updated_at,file_name,file_path,file_alias,file_size,code,uid',
      'sort_order'   => 'nullable|string|in:asc,desc',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $query = File::query()->where('type_id', FileType::DeviceFile->value);

    if ($request->boolean('with_trashed')) {
      $query->withTrashed();
    }

    if ($search = $request->input('search')) {
      $query->where(function ($q) use ($search) {
        $q->where('file_name', 'like', "%{$search}%")
          ->orWhere('file_path', 'like', "%{$search}%")
          ->orWhere('file_alias', 'like', "%{$search}%")
          ->orWhere('description', 'like', "%{$search}%")
          ->orWhere('code', 'like', "%{$search}%")
          ->orWhere('uid', 'like', "%{$search}%");
      });
    }

    $sortBy    = $request->input('sort_by', 'id');
    $sortOrder = $request->input('sort_order', 'desc');
    $query->orderBy($sortBy, $sortOrder);

    $perPage = (int) $request->input('per_page', 15);
    $files   = $query->paginate($perPage);

    return response()->json(new FileCollection($files));
  }

  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'uid'         => 'nullable|string|max:255',
      'file_name'   => 'nullable|string|max:255',
      'file_path'   => 'nullable|string|max:255',
      'file_size'   => 'nullable',
      'file_alias'  => 'nullable|string|max:255',
      'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    if ($request->filled('uid')) {
      $existingFile = File::query()
        ->where('uid', strtolower((string) $request->input('uid')))
        ->first();

      if ($existingFile) {
        return response()->json([
          'success' => false,
          'message' => 'Validation error',
          'errors'  => [
            'uid' => ['The uid has already been taken.'],
          ],
          'data'    => [
            'id'  => $existingFile->id,
            'uid' => $existingFile->uid,
          ],
        ], 422);
      }
    }

    $fileSize = 0;
    if ($request->has('file_size') && $request->input('file_size') !== null) {
      $fileSize = parseSizeToBytes($request->input('file_size'));
    }

    $file = File::create([
      'type_id'     => FileType::DeviceFile->value,
      'uid'         => $request->input('uid') ?: uuid7(),
      'file_name'   => $request->input('file_name'),
      'file_path'   => $request->input('file_path'),
      'file_size'   => $fileSize,
      'file_alias'  => $request->input('file_alias'),
      'description' => $request->input('description'),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Device file created successfully',
      'data'    => new FileResource($file),
    ], 201);
  }

  public function storeBatch(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'files'               => 'required|array|min:1',
      'files.*.uid'         => 'nullable|string|max:255',
      'files.*.file_name'   => 'nullable|string|max:255',
      'files.*.file_path'   => 'nullable|string|max:255',
      'files.*.file_size'   => 'nullable',
      'files.*.file_alias'  => 'nullable|string|max:255',
      'files.*.description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $items        = $request->input('files');
    $providedUids = [];

    foreach ($items as $item) {
      if (!empty($item['uid'])) {
        $providedUids[] = strtolower((string) $item['uid']);
      }
    }

    $existingMap = [];
    if (!empty($providedUids)) {
      $existingFiles = File::query()
        ->whereIn('uid', $providedUids)
        ->get(['id', 'uid']);

      foreach ($existingFiles as $file) {
        $existingMap[strtolower((string) $file->uid)] = $file->id;
      }
    }

    $now             = now();
    $userId          = getUser()?->id;
    $recordsToInsert = [];
    $newUids         = [];
    $seenUids        = [];
    $skippedItems    = [];

    foreach ($items as $item) {
      $rawUid = $item['uid'] ?? null;
      $uid    = $rawUid ? strtolower((string) $rawUid) : uuid7();

      if (isset($existingMap[$uid])) {
        $skippedItems[] = [
          'id'  => $existingMap[$uid],
          'uid' => $uid,
        ];
        continue;
      }

      if (in_array($uid, $seenUids, true)) {
        $skippedItems[] = [
          'id'  => null,
          'uid' => $uid,
        ];
        continue;
      }

      $seenUids[] = $uid;
      $newUids[]  = $uid;

      $fileSize = 0;
      if (isset($item['file_size']) && $item['file_size'] !== null) {
        $fileSize = parseSizeToBytes($item['file_size']);
      }

      $recordsToInsert[] = [
        'type_id'     => FileType::DeviceFile->value,
        'uid'         => $uid,
        'code'        => getCode('file'),
        'user_id'     => $userId,
        'file_name'   => $item['file_name'] ?? null,
        'file_path'   => $item['file_path'] ?? null,
        'file_size'   => $fileSize,
        'file_alias'  => $item['file_alias'] ?? null,
        'description' => $item['description'] ?? null,
        'created_at'  => $now,
        'updated_at'  => $now,
      ];
    }

    $createdFiles = collect();
    if (!empty($recordsToInsert)) {
      $createdFiles = DB::transaction(function () use ($recordsToInsert, $newUids) {
        File::insert($recordsToInsert);

        return File::query()->whereIn('uid', $newUids)->get();
      });
    }

    return response()->json([
      'success' => true,
      'message' => 'Device files batch processed successfully',
      'data'    => FileResource::collection($createdFiles),
      'meta'    => [
        'total'          => count($items),
        'inserted_count' => count($createdFiles),
        'skipped_count'  => count($skippedItems),
        'skipped'        => $skippedItems,
      ],
    ], 201);
  }

  public function show(string|int $id): JsonResponse
  {
    $file = File::query()
      ->where('type_id', FileType::DeviceFile->value)
      ->where(function ($q) use ($id) {
        $q->where('id', $id)->orWhere('uid', $id);
      })
      ->first();

    if (!$file) {
      return response()->json([
        'success' => false,
        'message' => 'Device file not found',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data'    => new FileResource($file),
    ]);
  }

  public function update(Request $request, string|int $id): JsonResponse
  {
    $file = File::query()
      ->where('type_id', FileType::DeviceFile->value)
      ->where(function ($q) use ($id) {
        $q->where('id', $id)->orWhere('uid', $id);
      })
      ->first();

    if (!$file) {
      return response()->json([
        'success' => false,
        'message' => 'Device file not found',
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'uid'         => 'nullable|string|max:255',
      'file_name'   => 'nullable|string|max:255',
      'file_path'   => 'nullable|string|max:255',
      'file_size'   => 'nullable',
      'file_alias'  => 'nullable|string|max:255',
      'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $data = [];

    if ($request->has('uid')) {
      $data['uid'] = $request->input('uid');
    }

    if ($request->has('file_name')) {
      $data['file_name'] = $request->input('file_name');
    }

    if ($request->has('file_path')) {
      $data['file_path'] = $request->input('file_path');
    }

    if ($request->has('file_size')) {
      $data['file_size'] = parseSizeToBytes($request->input('file_size'));
    }

    if ($request->has('file_alias')) {
      $data['file_alias'] = $request->input('file_alias');
    }

    if ($request->has('description')) {
      $data['description'] = $request->input('description');
    }

    $file->update($data);

    return response()->json([
      'success' => true,
      'message' => 'Device file updated successfully',
      'data'    => new FileResource($file->fresh()),
    ]);
  }

  public function destroy(Request $request, string|int $id): JsonResponse
  {
    $file = File::query()
      ->where('type_id', FileType::DeviceFile->value)
      ->where(function ($q) use ($id) {
        $q->where('id', $id)->orWhere('uid', $id);
      })
      ->first();

    if (!$file) {
      return response()->json([
        'success' => false,
        'message' => 'Device file not found',
      ], 404);
    }

    if ($request->boolean('force')) {
      $file->forceDelete();
    } else {
      $file->delete();
    }

    return response()->json([
      'success' => true,
      'message' => 'Device file deleted successfully',
    ]);
  }
}
