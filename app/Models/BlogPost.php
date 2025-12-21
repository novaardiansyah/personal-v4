<?php

namespace App\Models;

use App\Enums\BlogPostStatus;
use App\Observers\BlogPostObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BlogPostObserver::class])]
class BlogPost extends Model
{
  use SoftDeletes;
  protected $table = 'blog_posts';
  protected $guarded = ['id'];

  protected function casts(): array
  {
    return [
      'status' => BlogPostStatus::class,
      'published_at' => 'datetime',
      'scheduled_at' => 'datetime',
    ];
  }

  public function author(): BelongsTo
  {
    return $this->belongsTo(User::class, 'author_id');
  }

  public function category(): BelongsTo
  {
    return $this->belongsTo(BlogCategory::class, 'category_id');
  }
}
