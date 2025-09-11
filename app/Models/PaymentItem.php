<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{
  protected $guarded = ['id'];
  protected $table = 'payment_item';
}
