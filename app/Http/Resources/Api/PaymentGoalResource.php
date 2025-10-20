<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentGoalResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'          => $this->id,
      'code'        => $this->code,
      'name'        => $this->name,
      'description' => $this->description,
      'status'      => $this->status->name,

      'formatted' => [
        'amount'        => toIndonesianCurrency($this->amount ?? 0),
        'target_amount' => toIndonesianCurrency($this->target_amount ?? 0),
        'progress'      => $this->progress_percent . '%',
        'start_date'    => $this->start_date?->format('d/m/Y'),
        'target_date'   => $this->target_date?->format('d/m/Y'),
      ],

      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
    ];
  }
}