<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Observers\EmailObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([EmailObserver::class])]
class Email extends Model
{
  use SoftDeletes;
  protected $table = 'emails';
  protected $fillable = ['uid', 'name', 'email', 'subject', 'message', 'status', 'url_attachment'];
  protected $casts = [
    'status' => EmailStatus::class,
  ];

  public function emailTemplate(): BelongsTo
  {
    return $this->belongsTo(EmailTemplate::class, 'email_template_id', 'id');
  }
}
