<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'               => $this->id,
      'name'             => $this->name,
      'code'             => $this->code,
      'amount'           => $this->amount,
      'formatted_amount' => toIndonesianCurrency($this->amount),
      'type'             => $this->type->name,
      'type_id'          => $this->type->id,
      'created_at'       => $this->created_at,
      'updated_at'       => $this->updated_at,
    ];
  }
}