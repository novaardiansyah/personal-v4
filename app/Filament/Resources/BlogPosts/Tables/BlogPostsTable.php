<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use App\Enums\BlogPostStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BlogPostsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('title')
      ->columns([
        TextColumn::make('index')
          ->rowIndex()
          ->label('#'),
        ImageColumn::make('cover_image_url')
          ->label('Cover')
          ->circular(),
        TextColumn::make('title')
          ->searchable()
          ->wrap()
          ->limit(50),
        TextColumn::make('slug')
          ->searchable()
          ->copyable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('category.name')
          ->label('Category')
          ->badge()
          ->color('success')
          ->searchable(),
        TextColumn::make('author.name')
          ->label('Author')
          ->searchable(),
        TextColumn::make('status')
          ->badge()
          ->color(fn(BlogPostStatus $state) => $state->color())
          ->sortable(),
        TextColumn::make('view_count')
          ->label('Views')
          ->badge()
          ->color('warning')
          ->sortable(),
        TextColumn::make('published_at')
          ->label('Published')
          ->dateTime()
          ->sortable(),
        TextColumn::make('deleted_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->sinceTooltip()
          ->toggleable(isToggledHiddenByDefault: false),
      ])
      ->filters([
        SelectFilter::make('status')
          ->options(BlogPostStatus::class),
        SelectFilter::make('category')
          ->relationship('category', 'name'),
        TrashedFilter::make()
          ->native(false),
      ])
      ->defaultSort('created_at', 'desc')
      ->recordActions([
        ActionGroup::make([
          ViewAction::make(),
          EditAction::make(),
          DeleteAction::make(),
          ForceDeleteAction::make(),
          RestoreAction::make(),
        ]),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
          ForceDeleteBulkAction::make(),
          RestoreBulkAction::make(),
        ]),
      ]);
  }
}
