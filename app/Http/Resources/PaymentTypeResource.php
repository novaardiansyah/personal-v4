<?php

namespace App\Http\Resources;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTypeResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $array = [
      'id'         => $this->id,
      'name'       => $this->name,
      'is_default' => false,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];

    $default = PaymentType::EXPENSE;

    if ((int) $array['id'] === (int) $default) {
      $array['is_default'] = true;
    }

    return $array;
  }
}