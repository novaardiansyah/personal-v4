<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\BlogPost;
use App\Models\BlogSubscriber;

class GetBlogInsightsTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_blog_insights';
  }

  public function getDescription(): string
  {
    return 'Retrieve blog post statistics, recent posts, and newsletter subscriber counts.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'limit' => [
          'type'        => 'integer',
          'description' => 'Max recent posts to return (default 5, max 15).',
        ],
      ],
      'required'   => [],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $limit = min((int) ($arguments['limit'] ?? 5), 15);

    $recentPosts = BlogPost::with('category')
      ->latest('id')
      ->take($limit)
      ->get()
      ->map(function ($post) {
        return [
          'title'        => $post->title,
          'slug'         => $post->slug,
          'category'     => $post->category?->name ?? '-',
          'status'       => $post->status?->value ?? (string) $post->status,
          'published_at' => $post->published_at?->format('Y-m-d H:i'),
        ];
      })
      ->toArray();

    return [
      'total_posts'       => BlogPost::count(),
      'total_subscribers' => BlogSubscriber::count(),
      'recent_posts'      => $recentPosts,
    ];
  }
}
