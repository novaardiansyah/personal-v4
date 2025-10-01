<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentItemResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'code' => $this->code,
      'amount' => $this->amount,
      'formatted_amount' => toIndonesianCurrency($this->amount),
      'type' => [
        'id' => $this->type->id,
        'name' => $this->type->name
      ],
      // Pivot data untuk attached items
      'pivot_id' => $this->pivot->id ?? null,
      'item_code' => $this->pivot->item_code ?? null,
      'quantity' => $this->pivot->quantity ?? null,
      'price' => $this->pivot->price ?? null,
      'total' => $this->pivot->total ?? null,
      'created_at' => $this->pivot->created_at ?? null,
      'updated_at' => $this->pivot->updated_at ?? null,
    ];
  }
}