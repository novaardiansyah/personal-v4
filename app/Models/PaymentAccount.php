<?php

namespace App\Models;

use App\Enums\GallerySize;
use App\Observers\PaymentAccountObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[ObservedBy([PaymentAccountObserver::class])]
class PaymentAccount extends Model
{
  use SoftDeletes;

  protected $guarded = ['id'];

  protected $appends = ['logo_url'];

  public const PERMATA_BANK = 1;
  public const DANA = 2;
  public const JAGO_BANK = 3;
  public const TUNAI = 4;
  public const GOPAY = 5;
  public const OVO = 6;
  public const SEA_BANK = 7;

  public function getPaymentAccountNameAttribute(): string
  {
    return $this->name ?? 'Unknown';
  }

  public function getLogoUrlAttribute(): string|null
  {
    return $this->logo ? config('services.self.cdn_url') . '/' .  $this->logo : null;
  }

  public function payments(): HasMany
  {
    return $this->hasMany(Payment::class);
  }

  public function audit(float $newDeposit): array
  {
    $currentDeposit = $this->deposit;
    $diffDeposit = $currentDeposit - $newDeposit;
    $paymentType = $diffDeposit > 0 ? PaymentType::EXPENSE : PaymentType::INCOME;

    $payment = Payment::create([
      'code' => getCode('payment'),
      'name' => 'Audit : ' . $this->name,
      'type_id' => $paymentType,
      'user_id' => getUser()->id,
      'payment_account_id' => $this->id,
      'amount' => abs($diffDeposit),
      'has_items' => false,
      'attachments' => [],
      'date' => Carbon::now()->format('Y-m-d')
    ]);

    return [
      'payment_account' => $this,
      'payment' => $payment,
      'audit_details' => [
        'previous_deposit' => $currentDeposit,
        'new_deposit' => $newDeposit,
        'difference' => $diffDeposit,
        'absolute_difference' => abs($diffDeposit),
        'adjustment_type' => $diffDeposit < 0 ? 'increase' : 'decrease',
        'payment_type' => $paymentType
      ]
    ];
  }
}
