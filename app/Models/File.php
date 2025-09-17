<?php

namespace App\Models;

use App\Observers\FileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([FileObserver::class])]
class File extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];  
  protected $table = 'files';
  protected $casts = [
    'has_been_deleted' => 'boolean',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function removeFile(): void
  {
    if (empty($this->file_path)) return;

    foreach (['app', 'local', 'public'] as $disk) {
      if (Storage::disk($disk)->exists($this->file_path)) {
        Storage::disk($disk)->delete($this->file_path);
      }
    }

    $this->update([
      'has_been_deleted' => true
    ]);
  }
}
