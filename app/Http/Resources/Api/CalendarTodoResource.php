<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarTodoResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'            => $this->id,
      'code'          => $this->code,
      'event_id'      => $this->event_id,
      'title'         => $this->title,
      'description'   => $this->description,
      'priority'      => $this->priority?->value,
      'priority_label' => $this->priority?->label(),
      'priority_color' => $this->priority?->color(),
      'due_at'        => $this->due_at?->format('Y-m-d H:i:s'),
      'completed_at'  => $this->completed_at?->format('Y-m-d H:i:s'),
      'is_completed'  => !is_null($this->completed_at),
      'sort_order'    => $this->sort_order,
      'event'         => $this->whenLoaded('event', function () {
        return [
          'id'    => $this->event->id,
          'title' => $this->event->title,
        ];
      }),
      'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'    => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at'    => $this->deleted_at?->format('Y-m-d H:i:s'),
    ];
  }
}