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
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'amount' => $this->amount,
      'formatted_amount' => toIndonesianCurrency($this->amount),
      'date' => $this->date,
      'formatted_date' => Carbon::parse($this->date)->format('M d, Y'),
      'type_id' => $this->type_id,
      'type' => [
        'id' => $this->payment_type->id,
        'name' => $this->payment_type->name,
        'formatted_name' => strtolower($this->payment_type->name)
      ],
      'payment_account' => [
        'id' => $this->payment_account->id,
        'name' => $this->payment_account->name,
        'deposit' => $this->payment_account->deposit,
        'formatted_deposit' => toIndonesianCurrency($this->payment_account->deposit)
      ],
      'payment_account_to' => $this->payment_account_to ? [
        'id' => $this->payment_account_to->id,
        'name' => $this->payment_account_to->name,
        'deposit' => $this->payment_account_to->deposit,
        'formatted_deposit' => toIndonesianCurrency($this->payment_account_to->deposit)
      ] : null,
      'has_items' => $this->has_items,
      'has_charge' => $this->has_charge,
      'is_scheduled' => $this->is_scheduled,
      'attachments' => $this->attachments ? array_map(function ($attachment) {
        return [
          'path' => $attachment,
          'url' => Storage::disk('public')->url($attachment)
        ];
      }, $this->attachments) : [],
      'items' => $this->whenLoaded('items', function () {
        return $this->items->map(function ($item) {
          return [
            'id' => $item->id,
            'name' => $item->name,
            'item_code' => $item->pivot->item_code,
            'quantity' => $item->pivot->quantity,
            'price' => $item->pivot->price,
            'total' => $item->pivot->total,
            'formatted_price' => toIndonesianCurrency($item->pivot->price),
            'formatted_total' => toIndonesianCurrency($item->pivot->total)
          ];
        });
      }),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at
    ];
  }
}
