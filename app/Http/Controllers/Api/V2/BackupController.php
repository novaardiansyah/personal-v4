<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Enums\BackupJobStatus;
use App\Enums\BackupScheduleIntervalUnit;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\BackupCollection;
use App\Http\Resources\Api\V2\BackupJobResource;
use App\Http\Resources\Api\V2\BackupResource;
use App\Models\Backup;
use App\Models\BackupJob;
use App\Models\BackupSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'per_page'     => 'nullable|integer|min:1|max:100',
      'search'       => 'nullable|string|max:255',
      'type'         => 'nullable|string|in:full,database,files,incremental',
      'status'       => 'nullable|string|in:pending,success,failed',
      'with_trashed' => 'nullable|boolean',
      'sort_by'      => 'nullable|string|in:id,created_at,started_at,completed_at,duration,file_name,file_size',
      'sort_order'   => 'nullable|string|in:asc,desc',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $query = Backup::query();

    if ($request->boolean('with_trashed')) {
      $query->withTrashed();
    }

    if ($request->filled('type')) {
      $query->where('type', $request->type);
    }

    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('file_name', 'like', "%{$search}%")
          ->orWhere('uid', 'like', "%{$search}%")
          ->orWhere('message', 'like', "%{$search}%")
          ->orWhere('server_name', 'like', "%{$search}%");
      });
    }

    $sortBy    = $request->input('sort_by', 'created_at');
    $sortOrder = $request->input('sort_order', 'desc');
    $query->orderBy($sortBy, $sortOrder);

    $perPage = (int) $request->input('per_page', 15);
    $backups = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'data'    => new BackupCollection($backups),
    ]);
  }

  public function store(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'backup_job_id'   => 'nullable|integer|exists:backup_jobs,id',
      'type'            => 'required|string|in:full,database,files,incremental',
      'file_name'       => 'nullable|string|max:255',
      'file_path'       => 'nullable|string|max:255',
      'cloud_file_path' => 'nullable|string|max:255',
      'file_size'       => 'nullable|integer|min:0',
      'checksum'        => 'nullable|string|max:64',
      'status'          => 'nullable|string|in:pending,success,failed',
      'message'         => 'nullable|string',
      'server_name'     => 'nullable|string|max:255',
      'started_at'      => 'nullable|date',
      'completed_at'    => 'nullable|date',
      'duration'        => 'nullable|integer|min:0',
    ]);

    if ($validator->fails()) {
      $result = [
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ];
      return response()->json($result, 422);
    }

    $data   = $validator->validated();
    $backup = Backup::create($data);

    return response()->json([
      'success' => true,
      'message' => 'Backup created successfully',
      'data'    => new BackupResource($backup),
    ], 201);
  }

  public function show(Request $request, string $id): JsonResponse
  {
    $query = Backup::query();

    if ($request->boolean('with_trashed')) {
      $query->withTrashed();
    }

    $backup = $query->where(function ($q) use ($id) {
      $q->where('id', $id)
        ->orWhere('uid', $id);
    })->first();

    if (!$backup) {
      return response()->json([
        'success' => false,
        'message' => 'Backup not found',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'data'    => new BackupResource($backup),
    ]);
  }

  public function update(Request $request, string $id): JsonResponse
  {
    $backup = Backup::where('id', $id)
      ->orWhere('uid', $id)
      ->first();

    if (!$backup) {
      return response()->json([
        'success' => false,
        'message' => 'Backup not found',
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'backup_job_id'   => 'nullable|integer|exists:backup_jobs,id',
      'type'            => 'nullable|string|in:full,database,files,incremental',
      'file_name'       => 'nullable|string|max:255',
      'file_path'       => 'nullable|string|max:255',
      'cloud_file_path' => 'nullable|string|max:255',
      'file_size'       => 'nullable|string|max:100',
      'checksum'        => 'nullable|string|max:64',
      'status'          => 'nullable|string|in:pending,success,failed',
      'message'         => 'nullable|string',
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

    $backup->update($validator->validated());

    return response()->json([
      'success' => true,
      'message' => 'Backup updated successfully',
      'data'    => new BackupResource($backup),
    ]);
  }

  public function destroy(Request $request, string $id): JsonResponse
  {
    $backup = Backup::withTrashed()
      ->where(function ($q) use ($id) {
        $q->where('id', $id)
          ->orWhere('uid', $id);
      })
      ->first();

    if (!$backup) {
      return response()->json([
        'success' => false,
        'message' => 'Backup not found',
      ], 404);
    }

    if ($request->boolean('force')) {
      $backup->forceDelete();
    } else {
      $backup->delete();
    }

    return response()->json([
      'success' => true,
      'message' => 'Backup deleted successfully',
    ]);
  }

  public function restore(string $id): JsonResponse
  {
    $backup = Backup::onlyTrashed()
      ->where(function ($q) use ($id) {
        $q->where('id', $id)
          ->orWhere('uid', $id);
      })
      ->first();

    if (!$backup) {
      $activeBackup = Backup::where('id', $id)
        ->orWhere('uid', $id)
        ->first();

      if ($activeBackup) {
        return response()->json([
          'success' => true,
          'message' => 'Backup is already active',
          'data'    => new BackupResource($activeBackup),
        ]);
      }

      return response()->json([
        'success' => false,
        'message' => 'Trashed backup not found',
      ], 404);
    }

    $backup->restore();

    return response()->json([
      'success' => true,
      'message' => 'Backup restored successfully',
      'data'    => new BackupResource($backup),
    ]);
  }

  public function download(string $id): JsonResponse|StreamedResponse
  {
    $backup = Backup::where('id', $id)
      ->orWhere('uid', $id)
      ->first();

    if (!$backup) {
      return response()->json([
        'success' => false,
        'message' => 'Backup not found',
      ], 404);
    }

    if (!$backup->file_path || !Storage::disk('public')->exists($backup->file_path)) {
      return response()->json([
        'success' => false,
        'message' => 'Backup file does not exist on disk',
      ], 404);
    }

    return Storage::disk('public')->download($backup->file_path, $backup->file_name);
  }

  /**
   * Check and trigger due backup schedules
   *
   * Scans enabled backup schedules that are due for execution, creates BackupJob entries, and updates schedule timestamps.
   *
   * @response 200 {
   *   "success": true,
   *   "message": "Backup job created successfully",
   *   "data": [
   *     {
   *       "id": 1,
   *       "backup_schedule_id": 1,
   *       "status": "Running",
   *       "message": null,
   *       "started_at": "2026-08-01 23:30:00",
   *       "finished_at": null,
   *       "source_path": "/www/wwwroot",
   *       "destination_path": "/www/wwwroot/backup/sysadmin",
   *       "is_sync_cloud": true,
   *       "r2_destination_path": "/backups/projects",
   *       "cloud_destination_path": "/backups/projects",
   *       "expected_filename": "backup-20260801-233000-1.zip"
   *     }
   *   ]
   * }
   * @response 404 {
   *   "success": false,
   *   "message": "No backup schedules are due",
   *   "data": null
   * }
   */
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

  public function updateJob(Request $request, string $id): JsonResponse
  {
    $job = BackupJob::find($id);

    if (!$job) {
      return response()->json([
        'success' => false,
        'message' => 'Backup job not found',
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'status'      => 'nullable|string|in:pending,running,success,failed',
      'finished_at' => 'nullable|date',
      'message'     => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422);
    }

    $data = $validator->validated();

    if (isset($data['status']) && in_array($data['status'], ['success', 'failed'], true)) {
      if (empty($data['finished_at'])) {
        $data['finished_at'] = now();
      }
    }

    $job->update($data);
    $job->load('backupSchedule');

    return response()->json([
      'success' => true,
      'message' => 'Backup job updated successfully',
      'data'    => new BackupJobResource($job),
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
}
