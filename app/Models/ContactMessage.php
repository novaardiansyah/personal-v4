<?php

namespace App\Models;

use App\Observers\ContactMessageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ContactMessageObserver::class])]
class ContactMessage extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  protected $casts = [
    'is_read' => 'boolean',
  ];
}
