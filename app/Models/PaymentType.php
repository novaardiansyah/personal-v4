<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentType extends Model
{
  use SoftDeletes;
  
  protected $guarded = ['id'];

  public const EXPENSE = 1;
  public const INCOME = 2;
  public const TRANSFER = 3;
  public const WITHDRAWAL = 4;
  
  public function getPaymentTypeNameAttribute(): string
  {
    return $this->name ?? 'Unknown';
  }
}
