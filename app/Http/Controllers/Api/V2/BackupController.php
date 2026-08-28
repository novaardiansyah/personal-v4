<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Enums\BackupJobStatus;
use App\Enums\BackupScheduleIntervalUnit;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\BackupJobResource;
use App\Http\Resources\Api\V2\BackupResource;
use App\Http\Resources\Api\V2\BackupStorageResource;
use App\Models\Backup;
use App\Models\BackupJob;
use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class BackupController extends Controller
{
  public function checkSchedule(): JsonResponse
  {
    $now = now();

    $schedules = BackupSchedule::where('is_enabled', true)
      ->whereNotNull('next_backup_at')
      ->where('next_backup_at', '<=', $now)
      ->get();

    if ($schedules->isEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'No backup schedules are due',
        'data'    => null,
      ], 404);
    }

    $jobs = [];

    foreach ($schedules as $schedule) {
      $job = BackupJob::create([
        'backup_schedule_id' => $schedule->id,
        'storage_id'         => $schedule->storage_id,
        'status'             => BackupJobStatus::Running,
        'assigned_at'        => $now,
        'started_at'         => $now,
      ]);

      $intervalValue = (int) ($schedule->interval_value ?? 1);
      $intervalUnit  = $schedule->interval_unit;
      $unitValue     = $intervalUnit instanceof BackupScheduleIntervalUnit
        ? $intervalUnit->value
        : (string) $intervalUnit;

      $nextBackupAt = match ($unitValue) {
        'minutes' => $now->copy()->addMinutes($intervalValue),
        'hours'   => $now->copy()->addHours($intervalValue),
        'days'    => $now->copy()->addDays($intervalValue),
        'weeks'   => $now->copy()->addWeeks($intervalValue),
        'months'  => $now->copy()->addMonths($intervalValue),
        default   => $now->copy()->addDays($intervalValue),
      };

      $schedule->update([
        'last_backup_at' => $now,
        'next_backup_at' => $nextBackupAt,
      ]);

      $job->load('backupSchedule');
      $jobs[] = $job;
    }

    $data = BackupJobResource::collection($jobs);

    return response()->json([
      'success' => true,
      'message' => 'Backup job created successfully',
      'data'    => $data,
    ]);
  }

  public function storeJobReport(Request $request, ?string $id = null): JsonResponse
  {
    $jobId = $id ?? $request->input('backup_job_id') ?? $request->input('job_id');

    if (!$jobId) {
      return response()->json([
        'success' => false,
        'message' => 'Backup job ID is required',
      ], 422);
    }

    $job = BackupJob::find($jobId);

    if (!$job) {
      return response()->json([
        'success' => false,
        'message' => 'Backup job not found',
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'status'          => 'nullable|string|in:pending,running,success,failed',
      'job_status'      => 'nullable|string|in:pending,running,success,failed',
      'finished_at'     => 'nullable|date',
      'message'         => 'nullable|string',
      'type'            => 'nullable|string|in:full,database,files,incremental',
      'file_name'       => 'nullable|string|max:255',
      'file_path'       => 'nullable|string|max:255',
      'cloud_file_path' => 'nullable|string|max:255',
      'file_size'       => 'nullable|integer|min:0',
      'checksum'        => 'nullable|string|max:64',
      'server_name'     => 'nullable|string|max:255',
      'started_at'      => 'nullable|date',
      'completed_at'    => 'nullable|date',
      'duration'        => 'nullable|integer|min:0',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $status     = $request->input('job_status') ?? $request->input('status', 'success');
    $finishedAt = $request->input('finished_at');

    if (in_array($status, ['success', 'failed'], true) && empty($finishedAt)) {
      $finishedAt = now();
    }

    $jobData = [
      'status' => $status,
    ];

    if ($finishedAt !== null) {
      $jobData['finished_at'] = $finishedAt;
    }

    if ($request->has('message')) {
      $jobData['message'] = $request->input('message');
    }

    $job->update($jobData);
    $job->load('backupSchedule');

    $backupStatus = match ($status) {
      'success' => 'success',
      'failed'  => 'failed',
      default   => 'pending',
    };

    $startedAt   = $request->input('started_at') ?? $job->started_at ?? now();
    $completedAt = $request->input('completed_at') ?? $finishedAt;

    $duration = $request->input('duration');
    if ($duration === null && $startedAt && $completedAt) {
      $duration = (int) max(0, Carbon::parse($startedAt)->diffInSeconds(Carbon::parse($completedAt)));
    }

    $backupData = array_filter([
      'backup_job_id'   => $job->id,
      'storage_id'      => $job->storage_id,
      'type'            => $request->input('type', 'database'),
      'file_name'       => $request->input('file_name'),
      'file_path'       => $request->input('file_path'),
      'cloud_file_path' => $request->input('cloud_file_path'),
      'file_size'       => $request->input('file_size'),
      'checksum'        => $request->input('checksum'),
      'status'          => $backupStatus,
      'message'         => $request->input('message'),
      'server_name'     => $request->input('server_name'),
      'started_at'      => $startedAt,
      'completed_at'    => $completedAt,
      'duration'        => $duration,
    ], fn ($val) => !is_null($val));

    $backup = Backup::create($backupData);

    return response()->json([
      'success' => true,
      'message' => 'Backup job updated and report created successfully',
      'data'    => [
        'job'    => new BackupJobResource($job),
        'backup' => new BackupResource($backup),
      ],
    ], 201);
  }

  public function showStorage(BackupStorage $backupStorage): JsonResponse
  {
    $backupStorage->loadMissing('provider');

    return response()->json([
      'success' => true,
      'data'    => new BackupStorageResource($backupStorage),
    ]);
  }
}
