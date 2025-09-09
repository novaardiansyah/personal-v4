<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemType extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  public const PRODUCT = 1;
  public const SERVICE = 2;
}
