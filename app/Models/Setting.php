<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
  use SoftDeletes;
  protected $guarded = ['id'];
  protected $casts = [
    'has_options' => 'boolean',
    'options'     => 'array',
  ];

  public static function showPaymentCurrency(): bool
  {
    return getSetting('show_payment_currency') === 'yes';
  }
}
