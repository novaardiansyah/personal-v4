<?php

namespace App\Models;

use App\Observers\FileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([FileObserver::class])]
class File extends Model
{
  use SoftDeletes;

  protected $table = 'files';

  protected $fillable = ['code', 'user_id', 'file_download_id', 'file_name', 'file_path', 'file_size', 'download_url', 'scheduled_deletion_time', 'has_been_deleted', 'subject_type', 'subject_id'];

  protected $casts = [
    'scheduled_deletion_time' => 'datetime',
    'has_been_deleted' => 'boolean',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function removeFile(): void
  {
    if (empty($this->file_path))
      return;

    foreach (['app', 'local', 'public'] as $disk) {
      if (Storage::disk($disk)->exists($this->file_path)) {
        Storage::disk($disk)->delete($this->file_path);
      }
    }

    $this->update([
      'has_been_deleted' => true
    ]);

    $this->delete();
  }

  public function subject(): MorphTo
  {
    return $this->morphTo();
  }

  public function fileDownload(): BelongsTo
  {
    return $this->belongsTo(FileDownload::class, 'file_download_id', 'id');
  }
}
