<?php

namespace App\Models;

use App\Observers\PaymentGoalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([PaymentGoalObserver::class])]
class PaymentGoal extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  protected $casts = [
    'amount'           => 'integer',
    'target_amount'    => 'integer',
    'progress_percent' => 'integer',
    'start_date'       => 'date',
    'target_date'      => 'date',
  ];

  public function status(): BelongsTo
  {
    return $this->belongsTo(PaymentGoalStatus::class);
  }

  public function getProgressColor()
  {
    $progress = $this->progress_percent;
    $color    = 'danger';

    if ($progress >= 100) {
      $color = 'success';
    } elseif ($progress >= 75) {
      $color = 'primary';
    } elseif ($progress >= 50) {
      $color = 'info';
    } elseif ($progress >= 25) {
      $color = 'warning';
    }

    return $color;
  }
}
