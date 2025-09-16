<?php

namespace App\Models;

use App\Observers\ItemTypeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ItemTypeObserver::class])]
class ItemType extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  public const PRODUCT = 1;
  public const SERVICE = 2;
}
