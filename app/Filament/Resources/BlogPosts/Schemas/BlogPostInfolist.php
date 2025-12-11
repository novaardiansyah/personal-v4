<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\BlogPostStatus;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlogPostInfolist
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make()
          ->description('Post Content')
          ->schema([
            TextEntry::make('title')
              ->label('Title'),
            TextEntry::make('slug')
              ->label('Slug')
              ->copyable()
              ->badge()
              ->color('info'),
            TextEntry::make('excerpt')
              ->label('Excerpt')
              ->columnSpanFull(),
            TextEntry::make('content')
              ->label('Content')
              ->html()
              ->prose()
              ->columnSpanFull(),
          ])
          ->columns(2),

        Section::make()
          ->description('Cover Image')
          ->schema([
            ImageEntry::make('cover_image_url')
              ->label('Cover Image'),
            TextEntry::make('cover_image_alt')
              ->label('Image Alt Text'),
          ])
          ->columns(2)
          ->collapsible(),

        Section::make()
          ->description('Categorization')
          ->schema([
            TextEntry::make('category.name')
              ->label('Category')
              ->badge()
              ->color('success'),
            TextEntry::make('author.name')
              ->label('Author')
              ->badge()
              ->color('info'),
          ])
          ->columns(2),

        Section::make()
          ->description('SEO Settings')
          ->schema([
            TextEntry::make('meta_title')
              ->label('Meta Title'),
            TextEntry::make('meta_description')
              ->label('Meta Description'),
          ])
          ->columns(2)
          ->collapsible(),

        Section::make()
          ->description('Publishing & Stats')
          ->schema([
            TextEntry::make('status')
              ->label('Status')
              ->badge()
              ->color(fn(BlogPostStatus $state) => $state->color()),
            TextEntry::make('published_at')
              ->label('Published At')
              ->dateTime(),
            TextEntry::make('scheduled_at')
              ->label('Scheduled At')
              ->dateTime(),
            TextEntry::make('display_order')
              ->label('Display Order')
              ->badge()
              ->color('gray'),
            TextEntry::make('view_count')
              ->label('Views')
              ->badge()
              ->color('warning')
              ->numeric(),
          ])
          ->columns(3),

        Section::make()
          ->description('Timestamps')
          ->schema([
            TextEntry::make('created_at')
              ->label('Created At')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->label('Updated At')
              ->dateTime()
              ->sinceTooltip(),
            TextEntry::make('deleted_at')
              ->label('Deleted At')
              ->dateTime(),
          ])
          ->columns(3)
          ->collapsible(),
      ])
      ->columns(1);
  }
}
