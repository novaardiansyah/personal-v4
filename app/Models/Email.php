<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Observers\EmailObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([EmailObserver::class])]
class Email extends Model
{
  use SoftDeletes;
  protected $table = 'emails';
  protected $fillable = ['uid', 'name', 'email', 'subject', 'message', 'status', 'is_url_attachment'];
  protected $appends = ['url_attachment', 'attachments', 'size_attachments'];
  protected $casts = [
    'status' => EmailStatus::class,
    'is_url_attachment' => 'boolean',
  ];

  public function files(): MorphMany
  {
    return $this->morphMany(File::class, 'subject');
  }

  protected function UrlAttachment(): Attribute
  {
    $url = getSetting('portfolio_url');
    return Attribute::make(
      get: fn (): string => $url . '/files/d/' . $this->uid,
    );
  }
  
  protected function Attachments(): Attribute
  {
    return Attribute::make(
      get: fn (): array => $this->files()->get()->map(function (File $file) {
        return $file->file_path;
      })->toArray(),
    );
  }

  protected function SizeAttachments(): Attribute
  {
    return Attribute::make(
      get: fn (): int => (int) $this->files()->get()->sum('file_size'),
    );
  }
}
