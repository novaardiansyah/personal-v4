<?php

namespace App\Models;

use App\Observers\SettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([SettingObserver::class])]
class Setting extends Model
{
  use SoftDeletes;
  protected $guarded = ['id'];
  protected $casts = [
    'has_options' => 'boolean',
    'options'     => 'array',
  ];

  public function subject(): MorphTo
  {
    return $this->morphTo();
  }

  public static function showPaymentCurrency(): bool
  {
    return textLower(getSetting('show_payment_currency')) === 'yes';
  }
}
