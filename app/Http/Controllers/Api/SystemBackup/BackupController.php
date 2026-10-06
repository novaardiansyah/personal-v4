<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SystemBackup;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\BackupSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BackupController extends Controller
{
  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'schedule_id'     => 'required|integer|exists:backup_schedules,id',
      'storage_id'      => 'nullable|integer|exists:backup_storages,id',
      'server_id'       => 'nullable|integer|exists:backup_storages,id',
      'file_name'       => 'nullable|string|max:255',
      'file_path'       => 'nullable|string|max:2000',
      'cloud_file_path' => 'nullable|string|max:2000',
      'file_size'       => 'nullable|integer|min:0',
      'checksum'        => 'nullable|string|max:64',
      'type'            => 'nullable|string|in:full,database,files,incremental',
      'started_at'      => 'nullable|date',
      'completed_at'    => 'nullable|date',
      'duration'        => 'nullable|integer|min:0',
      'status'          => 'nullable|string|in:pending,success,failed',
      'message'         => 'nullable|string',
      'next_backup_at'  => 'nullable|date',
      'last_backup_at'  => 'nullable|date',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $validated = $validator->validated();
    $schedule  = BackupSchedule::findOrFail($validated['schedule_id']);

    $storageId = $validated['storage_id'] ?? $schedule->storage_id;
    $serverId  = $validated['server_id'] ?? $schedule->server_id;
    $type      = $validated['type'] ?? ($schedule->type instanceof BackupType ? $schedule->type->value : $schedule->type);
    $fileName  = $validated['file_name'] ?? ($schedule->filename_pattern ? BackupSchedule::generateFilename($schedule->filename_pattern) : null);
    $fileSize  = isset($validated['file_size']) ? (int) $validated['file_size'] : 0;

    $startedAt   = isset($validated['started_at']) ? Carbon::parse($validated['started_at']) : now();
    $completedAt = isset($validated['completed_at']) ? Carbon::parse($validated['completed_at']) : now();

    $duration = $validated['duration'] ?? ($startedAt && $completedAt ? max(0, (int) $startedAt->diffInSeconds($completedAt)) : 0);

    $lastBackupAt = isset($validated['last_backup_at'])
      ? Carbon::parse($validated['last_backup_at'])
      : $completedAt;

    $nextBackupAt = isset($validated['next_backup_at'])
      ? Carbon::parse($validated['next_backup_at'])
      : $schedule->calculateNextBackupAt($lastBackupAt);

    $backupData = [
      'schedule_id'     => $schedule->id,
      'storage_id'      => $storageId,
      'server_id'       => $serverId,
      'type'            => $type,
      'file_name'       => $fileName,
      'file_path'       => $validated['file_path'] ?? null,
      'cloud_file_path' => $validated['cloud_file_path'] ?? null,
      'file_size'       => $validated['file_size'] ?? null,
      'checksum'        => $validated['checksum'] ?? null,
      'started_at'      => $startedAt,
      'completed_at'    => $completedAt,
      'duration'        => $duration,
      'status'          => $validated['status'] ?? BackupStatus::Success->value,
      'message'         => $validated['message'] ?? null,
    ];

    $backup = DB::transaction(function () use ($backupData, $schedule, $lastBackupAt, $nextBackupAt, $fileSize) {
      $backup = Backup::create($backupData);

      $schedule->count_backup   = (int) ($schedule->count_backup ?? 0) + 1;
      $schedule->sum_file_size  = (int) ($schedule->sum_file_size ?? 0) + $fileSize;
      $schedule->last_backup_at = $lastBackupAt;
      $schedule->next_backup_at = $nextBackupAt;
      $schedule->save();

      return $backup;
    });

    return response()->json([
      'success' => true,
      'message' => 'Backup stored successfully',
      'data'    => $backup,
    ], 201);
  }
}
