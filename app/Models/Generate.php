<?php

namespace App\Models;

use App\Observers\GenerateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([GenerateObserver::class])]
class Generate extends Model
{
  use SoftDeletes;
  
  protected $guarded = ['id'];

  public function getNextId(): string
  {
    return getCode($this->alias, false);
  }
}
