<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Observers\EmailObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([EmailObserver::class])]
class Email extends Model
{
  use SoftDeletes;
  protected $table = 'emails';
  protected $fillable = ['name', 'email', 'subject', 'message', 'status', 'attachments'];
  protected $casts = [
    'status'      => EmailStatus::class,
    'attachments' => 'array',
  ];
}
