<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
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
      'code'             => $this->code,
      'title'            => $this->title,
      'description'      => $this->description,
      'location'         => $this->location,
      'start_at'         => $this->start_at?->format('Y-m-d H:i:s'),
      'end_at'           => $this->end_at?->format('Y-m-d H:i:s'),
      'is_all_day'       => $this->is_all_day,
      'category_id'      => $this->category_id,
      'color'            => $this->color,
      'recurrence_type'  => $this->recurrence_type,
      'recurrence_interval' => $this->recurrence_interval,
      'recurrence_end_at'   => $this->recurrence_end_at?->format('Y-m-d H:i:s'),
      'recurring_event_id'  => $this->recurring_event_id,
      'source_type'      => $this->source_type,
      'source_id'        => $this->source_id,
      'metadata'         => $this->metadata,
      'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at'       => $this->updated_at?->format('Y-m-d H:i:s'),
      'deleted_at'       => $this->deleted_at?->format('Y-m-d H:i:s'),
      'category'         => $this->whenLoaded('category', function () {
        return [
          'id'   => $this->category->id,
          'name' => $this->category->name,
          'color' => $this->category->color,
        ];
      }),
      'source'           => $this->whenLoaded('source', function () {
        return [
          'type' => $this->source_type,
          'id'   => $this->source_id,
        ];
      }),
      'reminders_count'  => $this->whenCounted('reminders'),
      'todos_count'      => $this->whenCounted('todos'),
    ];
  }
}