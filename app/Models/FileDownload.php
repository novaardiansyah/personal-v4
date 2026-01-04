<?php

namespace App\Models;

use App\Enums\FileDownloadStatus;
use App\Observers\FileDownloadObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[ObservedBy([FileDownloadObserver::class])]
class FileDownload extends Model
{
  use SoftDeletes;
  protected $table = 'file_downloads';
  protected $fillable = ['uid', 'code', 'status', 'download_count', 'access_count'];
  protected $appends = ['download_url'];

  protected $casts = [
    'status' => FileDownloadStatus::class,
    'download_count' => 'integer',
    'access_count' => 'integer',
  ];

  public function files(): HasMany
  {
    return $this->hasMany(File::class, 'file_download_id', 'id');
  }

  protected function DownloadUrl(): Attribute
  {
    $url = getSetting('portfolio_url');
    return Attribute::make(
      get: fn (): string => $url . '/files/d/' . $this->uid,
    );
  }
}
