<?php

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
  use SoftDeletes;
  protected $table = 'emails';
  protected $fillable = ['name', 'email', 'subject', 'message', 'status'];
  protected $casts = [
    'status'  => EmailStatus::class,
  ];
}
