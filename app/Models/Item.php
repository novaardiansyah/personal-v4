<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
  use SoftDeletes;
  
  protected $guarded = ['id'];

  public function type(): BelongsTo
  {
    return $this->belongsTo(ItemType::class,  'type_id');
  }
}
