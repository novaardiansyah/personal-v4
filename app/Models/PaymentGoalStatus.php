<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGoalStatus extends Model
{
  use SoftDeletes;
  
  protected $table = 'payment_goal_statuses';
  protected $fillable = ['name'];

  public const ONGOING = 1;
  public const OVERDUE = 2;
  public const COMPLETED = 3;

  public function getBadgeColors(): string
  {
    $status = $this->id;

    return match ($status) {
      self::ONGOING   => 'info',
      self::OVERDUE   => 'danger',
      self::COMPLETED => 'success',
      default         => 'secondary',
    };
  }
}
