<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Filament\Resources\BlogPosts\Pages\ViewBlogPost;
use App\Filament\Resources\BlogPosts\Schemas\BlogPostForm;
use App\Filament\Resources\BlogPosts\Schemas\BlogPostInfolist;
use App\Filament\Resources\BlogPosts\Tables\BlogPostsTable;
use App\Models\BlogPost;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlogPostResource extends Resource
{
  protected static ?string $model = BlogPost::class;
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
  protected static string|UnitEnum|null $navigationGroup = 'Blog';
  protected static ?string $modelLabel = 'Post';
  protected static ?string $pluralModelLabel = 'Posts';
  protected static ?int $navigationSort = 10;
  protected static ?string $recordTitleAttribute = 'title';

  public static function form(Schema $schema): Schema
  {
    return BlogPostForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return BlogPostInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return BlogPostsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => ListBlogPosts::route('/'),
      'create' => CreateBlogPost::route('/create'),
      'view'   => ViewBlogPost::route('/{record}'),
      'edit'   => EditBlogPost::route('/{record}/edit'),
    ];
  }

  public static function getRecordRouteBindingEloquentQuery(): Builder
  {
    return parent::getRecordRouteBindingEloquentQuery()
      ->withoutGlobalScopes([
        SoftDeletingScope::class,
      ]);
  }
}
