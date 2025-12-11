<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\BlogPostStatus;
use App\Models\BlogCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogPostForm
{
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Tabs::make('Post')
          ->tabs([
            Tab::make('Content')
              ->icon('heroicon-o-document-text')
              ->schema([
                TextInput::make('title')
                  ->required()
                  ->live(onBlur: true)
                  ->afterStateUpdated(function (Set $set, ?string $state) {
                    $set('slug', Str::slug($state));
                  }),
                TextInput::make('slug')
                  ->disabled()
                  ->dehydrated()
                  ->required(),
                Textarea::make('excerpt')
                  ->rows(3)
                  ->columnSpanFull(),
                RichEditor::make('content')
                  ->required()
                  ->columnSpanFull(),
              ])
              ->columns(2),

            Tab::make('Media')
              ->icon('heroicon-o-photo')
              ->schema([
                FileUpload::make('cover_image_url')
                  ->label('Cover Image')
                  ->image()
                  ->directory('blog/covers')
                  ->columnSpanFull(),
                Textarea::make('cover_image_alt')
                  ->label('Alt Text')
                  ->rows(2)
                  ->columnSpanFull(),
              ]),

            Tab::make('Categorization')
              ->icon('heroicon-o-folder')
              ->schema([
                Select::make('category_id')
                  ->label('Category')
                  ->options(BlogCategory::query()->pluck('name', 'id'))
                  ->searchable()
                  ->preload()
                  ->native(false),
                Select::make('author_id')
                  ->label('Author')
                  ->relationship('author', 'name')
                  ->searchable()
                  ->preload()
                  ->required()
                  ->default(auth()->id())
                  ->native(false),
              ])
              ->columns(2),

            Tab::make('SEO')
              ->icon('heroicon-o-magnifying-glass')
              ->schema([
                TextInput::make('meta_title')
                  ->label('Meta Title')
                  ->maxLength(60)
                  ->helperText('Max 60 characters'),
                TextInput::make('meta_description')
                  ->label('Meta Description')
                  ->maxLength(160)
                  ->helperText('Max 160 characters'),
              ])
              ->columns(2),

            Tab::make('Publishing')
              ->icon('heroicon-o-clock')
              ->schema([
                Select::make('status')
                  ->options(BlogPostStatus::class)
                  ->default(BlogPostStatus::Draft)
                  ->required()
                  ->native(false)
                  ->live(),
                DateTimePicker::make('published_at')
                  ->label('Published At')
                  ->native(false)
                  ->default(now())
                  ->visible(fn($get) => $get('status') === BlogPostStatus::Published),
                DateTimePicker::make('scheduled_at')
                  ->label('Scheduled At')
                  ->native(false)
                  ->default(now())
                  ->visible(fn($get) => $get('status') === BlogPostStatus::Scheduled),
                TextInput::make('display_order')
                  ->numeric()
                  ->default(0),
              ])
              ->columns(3),
          ])
          ->columnSpanFull(),
      ]);
  }
}
