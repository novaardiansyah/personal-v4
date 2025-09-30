<?php

namespace App\Http\Resources;

use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PaymentAccountResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $array = [
      'id'                => $this->id,
      'name'              => $this->name,
      'deposit'           => $this->deposit,
      'formatted_deposit' => toIndonesianCurrency($this->deposit),
      'logo'              => Storage::disk('public')->url($this->logo),
      'is_default'        => false,
    ];

    $default = PaymentAccount::TUNAI;

    if ((int) $array['id'] === (int) $default) {
      $array['is_default'] = true;
    }

    return $array;
  }
}