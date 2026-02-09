<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\HttpStatusObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([HttpStatusObserver::class])]
class HttpStatus extends Model
{
  protected $table = 'http_statuses';

  protected $fillable = ['name', 'message', 'description'];

  protected $appends = ['label'];

  public function getLabelAttribute(): string
  {
    return "{$this->name} ({$this->message})";
  }
}
