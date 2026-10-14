<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SystemBackup;

use App\Http\Controllers\Controller;
use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupScheduleController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $serverSlug = trim((string) $request->input('server_slug', ''));

    if (empty($serverSlug)) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => [
          'server_slug' => ['The server_slug parameter is required.'],
        ],
      ], 422);
    }

    $server = BackupStorage::where('slug', $serverSlug)->first();

    if (! $server) {
      return response()->json([
        'success' => false,
        'message' => 'Server not found',
        'errors'  => [
          'server_slug' => ["Server with slug '{$serverSlug}' was not found."],
        ],
      ], 404);
    }

    $perPage = (int) $request->input('per_page', 10);
    if ($perPage <= 0 || $perPage > 10) {
      $perPage = 10;
    }

    $query = BackupSchedule::query()
      ->where('server_id', $server->id)
      ->whereNotNull('next_backup_at')
      ->where('next_backup_at', '<=', now())
      ->with('storage.provider')
      ->orderBy('next_backup_at', 'asc');

    if (! $request->boolean('include_disabled')) {
      $query->where('is_enabled', true);
    }

    $schedules = $query->paginate($perPage);

    $items = collect($schedules->items())->map(function (BackupSchedule $schedule): array {
      $data = $schedule->toArray();

      $storage = $schedule->storage;
      $keys    = $storage?->keys;

      if ((int) $storage?->provider_id === 1 && $storage?->provider) {
        $providerKeys = $storage->provider->keys ?? [];
        $keys         = is_array($keys) ? $keys : [];

        $keys['oauth_client_id']     = $providerKeys['oauth_client_id'] ?? null;
        $keys['oauth_client_secret'] = $providerKeys['oauth_client_secret'] ?? null;
      }

      $data['filename']               = BackupSchedule::parsePattern($schedule->filename_pattern);
      $data['cloud_destination_path'] = BackupSchedule::parsePattern($schedule->r2_destination_path);
      $data['local_destination_path'] = BackupSchedule::parsePattern($schedule->local_destination_path);
      $data['keys']                   = $keys;

      unset(
        $data['server_id'],
        $data['storage'],
        $data['filename_pattern'],
        $data['r2_destination_path'],
        $data['uid'],
        $data['interval_value'],
        $data['interval_unit'],
        $data['deleted_at'],
        $data['created_at'],
        $data['updated_at'],
        $data['next_backup_at'],
        $data['last_backup_at'],
        $data['count_backup'],
        $data['max_count_backup'],
        $data['sum_file_size'],
      );

      $ordered = [];
      foreach ($data as $key => $value) {
        $ordered[$key] = $value;
        if ($key === 'storage_id') {
          $ordered['provider_id'] = $storage?->provider_id !== null ? (int) $storage->provider_id : null;
        }
      }

      return $ordered;
    });

    return response()->json([
      'success'    => true,
      'message'    => 'Backup schedules retrieved successfully',
      'data'       => $items,
      'pagination' => [
        'current_page' => $schedules->currentPage(),
        'from'         => $schedules->firstItem(),
        'last_page'    => $schedules->lastPage(),
        'per_page'     => $schedules->perPage(),
        'to'           => $schedules->lastItem(),
        'total'        => $schedules->total(),
      ],
    ]);
  }

  public function start(int|string $id): JsonResponse
  {
    $schedule = BackupSchedule::find($id);

    if (! $schedule) {
      return response()->json([
        'success' => false,
        'message' => 'Backup schedule not found',
      ], 404);
    }

    $schedule->next_backup_at = $schedule->calculateNextBackupAt(now());
    $schedule->updated_at     = now();
    $schedule->save();

    return response()->json([
      'success' => true,
      'message' => 'Backup schedule started and next_backup_at updated successfully',
      'data'    => [
        'id'             => $schedule->id,
        'next_backup_at' => $schedule->next_backup_at,
        'updated_at'     => $schedule->updated_at,
      ],
    ]);
  }
}
