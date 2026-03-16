<?php

/*
 * Project Name: personal-v4
 * File: PaymentType.php
 * Created Date: Sunday January 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Models;

use App\Observers\PaymentTypeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(PaymentTypeObserver::class)]
class PaymentType extends Model
{
  use SoftDeletes;

	protected $table = 'payment_types';

  protected $fillable = ['uid', 'name'];

  public const EXPENSE = 1;
  public const INCOME = 2;
  public const TRANSFER = 3;
  public const WITHDRAWAL = 4;
  
  public function getPaymentTypeNameAttribute(): string
  {
    return $this->name ?? 'Unknown';
  }
}
