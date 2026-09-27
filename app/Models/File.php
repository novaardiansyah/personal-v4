<?php

namespace App\Models;

use App\Observers\FileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

  protected $fillable = ['uid', 'type_id', 'description', 'code', 'user_id', 'file_download_id', 'file_name', 'file_path', 'file_size', 'download_url', 'scheduled_deletion_time', 'has_been_deleted', 'subject_type', 'subject_id', 'file_alias', 'encrypt_key'];

  protected $casts = [
    'scheduled_deletion_time' => 'datetime',
    'has_been_deleted'        => 'boolean',
  ];

  protected function uid(): Attribute
  {
    return Attribute::make(
      get: fn(?string $value): ?string => $value ? strtolower($value) : null,
      set: fn(?string $value): ?string => $value ? strtolower($value) : null,
    );
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function removeFile(): void
  {
    if (empty($this->file_path))
      return;

    foreach (['app', 'local', 'public', 'rustfs'] as $disk) {
      if (Storage::disk($disk)->exists($this->file_path)) {
        Storage::disk($disk)->delete($this->file_path);
      }
    }

    if (!$this->has_been_deleted) {
      $this->updateQuietly([
        'has_been_deleted' => true
      ]);
    }
  }

  public function subject(): MorphTo
  {
    return $this->morphTo();
  }

  public function fileDownload(): BelongsTo
  {
    return $this->belongsTo(FileDownload::class, 'file_download_id', 'id');
  }

	public function type(): BelongsTo
	{
		return $this->belongsTo(FileType::class, 'type_id');
	}

  public function isImage(): bool
  {
    if (empty($this->file_name) && empty($this->file_path)) {
      return false;
    }

    $extension = strtolower(pathinfo($this->file_name ?: $this->file_path, PATHINFO_EXTENSION));

    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif']);
  }
}
