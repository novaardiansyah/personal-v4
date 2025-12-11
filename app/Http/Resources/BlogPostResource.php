<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlogPostResource extends JsonResource
{
  /**
   * Transform the resource into an array.
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
      'content'                => $this->content,
      'cover_image_url'        => $this->cover_image_url ? Storage::disk('public')->url($this->cover_image_url) : null,
      'cover_image_alt'        => $this->cover_image_alt,
      'meta_title'             => $this->meta_title,
      'meta_description'       => $this->meta_description,
      'status'                 => $this->status?->value,
      'status_label'           => $this->status?->label(),
      'display_order'          => $this->display_order,
      'view_count'             => $this->view_count,
      'published_at'           => $this->published_at,
      'formatted_published_at' => $this->published_at ? Carbon::parse($this->published_at)->format('M d, Y') : null,
      'scheduled_at'           => $this->scheduled_at,
      'created_at'             => $this->created_at,
      'updated_at'             => $this->updated_at,
      'author' => $this->whenLoaded('author', fn() => [
        'id'    => $this->author->id,
        'name'  => $this->author->name,
        'email' => $this->author->email,
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
