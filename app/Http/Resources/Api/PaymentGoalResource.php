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
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'description' => $this->description,
      'amount' => $this->amount,
      'target_amount' => $this->target_amount,
      'progress_percent' => $this->progress_percent,
      'progress_color' => $this->getProgressColor(),
      'start_date' => $this->start_date?->format('Y-m-d'),
      'target_date' => $this->target_date?->format('Y-m-d'),

      // Status relationship
      'status' => [
        'id' => $this->status->id,
        'name' => $this->status->name,
        'badge_color' => $this->status->getBadgeColors(),
      ],

      // Formatted financial values
      'formatted' => [
        'amount' => toIndonesianCurrency($this->amount ?? 0),
        'target_amount' => toIndonesianCurrency($this->target_amount ?? 0),
        'progress' => $this->progress_percent . '%',
      ],

      // Timestamps
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
      'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}