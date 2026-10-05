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
      ->orderBy('next_backup_at', 'asc');

    if (! $request->boolean('include_disabled')) {
      $query->where('is_enabled', true);
    }

    $schedules = $query->paginate($perPage);

    $items = collect($schedules->items())->map(function (BackupSchedule $schedule): array {
      $data = $schedule->toArray();

      $data['filename']               = BackupSchedule::parsePattern($schedule->filename_pattern);
      $data['cloud_destination_path'] = BackupSchedule::parsePattern($schedule->r2_destination_path);
      $data['local_destination_path'] = BackupSchedule::parsePattern($schedule->local_destination_path);

      unset($data['filename_pattern'], $data['r2_destination_path']);

      return $data;
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
}
