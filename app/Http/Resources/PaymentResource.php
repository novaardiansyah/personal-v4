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
   * ⚠️ MOBILE APP USAGE WARNING:
   * This method is used by NovaApp mobile app for /payments/recent-transactions API.
   * The following fields are REQUIRED for mobile app compatibility:
   * - 'title' (string): Transaction title displayed in mobile app
   * - 'type' (string): Lowercase payment type for mobile app styling and icons
   *
   * DO NOT change these fields without updating the mobile app first!
   * Mobile app expects: Transaction interface { id, title, amount, type, date }
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'title' => $this->name ?? $this->payment_type->name, // ⚠️ MOBILE APP: REQUIRED field
      'amount' => $this->amount,
      'formatted_amount' => toIndonesianCurrency($this->amount),
      'date' => $this->date,
      'type' => strtolower($this->payment_type->name), // ⚠️ MOBILE APP: REQUIRED field (lowercase)
      'type_id' => $this->payment_type->id,
      'payment_account' => [
        'id' => $this->payment_account->id,
        'name' => $this->payment_account->name,
      ],
      'payment_account_to' => $this->payment_account_to ? [
        'id' => $this->payment_account_to->id,
        'name' => $this->payment_account_to->name,
      ] : null,
      'has_items' => $this->has_items,
      'attachments_count' => $this->attachments ? count($this->attachments) : 0,
      'items_count' => $this->whenLoaded('items', function () {
        return $this->items->count();
      }),
      'items' => $this->whenLoaded('items', function () {
        return $this->items->map(function ($item) {
          return [
            'id' => $item->id,
            'name' => $item->name,
            'quantity' => $item->pivot->quantity,
            'price' => $item->pivot->price,
            'total' => $item->pivot->total,
          ];
        });
      }),
    ];
  }
}
