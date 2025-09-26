<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'     => $this->id,
      'title'  => $this->name ?? $this->payment_type->name,
      'amount' => $this->amount,
      'type'   => strtolower($this->payment_type->name),
      'date'   => $this->date,
    ];
  }
}
