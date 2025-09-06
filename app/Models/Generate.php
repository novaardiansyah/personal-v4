<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Generate extends Model
{
  use SoftDeletes;
  
  protected $guarded = ['id'];

  public function getNextId(): string
  {
    return getCode($this->alias, false);
  }
}
