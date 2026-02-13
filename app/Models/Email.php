<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Observers\EmailObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[ObservedBy([EmailObserver::class])]
class Email extends Model
{
  use SoftDeletes;

  protected $table = 'emails';
  
  protected $fillable = ['uid', 'name', 'email', 'subject', 'message', 'status', 'url_attachment', 'has_header', 'has_footer'];
  
  protected $casts = [
    'status'     => EmailStatus::class,
    'has_header' => 'boolean',
    'has_footer' => 'boolean',
  ];

  public function emailTemplate(): BelongsTo
  {
    return $this->belongsTo(EmailTemplate::class, 'email_template_id', 'id');
  }

  public function files(): MorphMany
  {
    return $this->MorphMany(File::class, 'subject');
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
