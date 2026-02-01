<?php

namespace App\Http\Resources;

use Illuminate\Support\Carbon;;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlogPostListResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   * Lightweight resource for listing (without content)
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id'                     => $this->id,
      'title'                  => $this->title,
      'slug'                   => $this->slug,
      'excerpt'                => $this->excerpt,
      'cover_image_url'        => $this->cover_image_url ? Storage::disk('public')->url($this->cover_image_url) : null,
      'cover_image_alt'        => $this->cover_image_alt,
      'status'                 => $this->status?->value,
      'status_label'           => $this->status?->label(),
      'display_order'          => $this->display_order,
      'view_count'             => $this->view_count,
      'published_at'           => $this->published_at,
      'formatted_published_at' => $this->published_at ? Carbon::parse($this->published_at)->format('M d, Y') : null,
      'author' => $this->whenLoaded('author', fn() => [
        'id'   => $this->author->id,
        'name' => $this->author->name,
      ]),
      'category' => $this->whenLoaded('category', fn() => [
        'id'        => $this->category?->id,
        'name'      => $this->category?->name,
        'slug'      => $this->category?->slug,
        'color_hex' => $this->category?->color_hex,
      ]),
    ];
  }
}
