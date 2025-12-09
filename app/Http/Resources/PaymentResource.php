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
      'id'                   => $this->id,
      'code'                 => $this->code,
      'name'                 => $this->name ?? '-',
      'date'                 => $this->date,
      'amount'               => $this->amount,
      'has_items'            => $this->has_items,
      'is_scheduled'         => $this->is_scheduled,
      'formatted_amount'     => toIndonesianCurrency($this->amount),
      'formatted_date'       => Carbon::parse($this->date)->format('M d, Y'),
      'type'                 => strtolower($this->payment_type->name),
      'type_id'              => $this->payment_type->id,
      'updated_at'           => $this->updated_at,
      'formatted_updated_at' => Carbon::parse($this->updated_at)->format('M d, Y - H:i'),
      'attachments_count'    => $this->getAttachmentsCount(),
      'items_count'          => $this->getItemsCount(),
      'account'              => [
        'id' => $this->payment_account->id ?? null,
        'name' => $this->payment_account->name ?? null,
      ],
      'account_to'           => [
        'id' => $this->payment_account_to->id ?? null,
        'name' => $this->payment_account_to->name ?? null,
      ],
    ];
  }
}
