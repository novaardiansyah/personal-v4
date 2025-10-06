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
    $price    = $this->pivot->price ?? 0;
    $quantity = $this->pivot->quantity ?? 1;
    $total    = $price * $quantity;

    return [
      'id'              => $this->pivot->id ?? null,
      'name'            => $this->name,
      'type_id'         => $this->type_id,
      'type'            => $this->type->name,
      'code'            => $this->pivot->item_code ?? null,
      'price'           => $price,
      'quantity'        => $quantity,
      'total'           => $total,
      'formatted_price' => toIndonesianCurrency($price),
      'formatted_total' => toIndonesianCurrency($total),
      'updated_at'      => $this->pivot->updated_at ?? null,
    ];
  }
}