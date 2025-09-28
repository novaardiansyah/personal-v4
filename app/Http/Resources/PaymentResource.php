<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PaymentResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * ⚠️ MOBILE APP: Used by NovaApp - don't change title/type fields
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'date' => $this->date,
      'amount' => $this->amount,
      'formatted_amount' => toIndonesianCurrency($this->amount),
      'formatted_date' => Carbon::parse($this->date)->format('d M Y'),
      'type' => strtolower($this->payment_type->name),
      'type_id' => $this->payment_type->id,
      'updated_at' => $this->updated_at,
    ];
  }
}
