<?php

namespace App\Filament\Resources\PaymentCategories\Tables;

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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentCategoriesTable
{
	public static function configure(Table $table): Table
	{
		return $table
			->columns([
				TextColumn::make('index')
					->rowIndex()
					->label('#'),
				TextColumn::make('code')
					->label('Category ID')
					->badge()
					->copyable()
					->searchable()
					->sortable(),
				TextColumn::make('user.name')
					->label('Owner')
					->searchable(),
				TextColumn::make('name')
					->label('Category')
					->sortable()
					->searchable(),
				IconColumn::make('is_default')
					->label('Default')
					->boolean()
					->sortable(),
				TextColumn::make('created_at')
					->dateTime()
					->sortable()
					->sinceTooltip()
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('updated_at')
					->dateTime()
					->sortable()
					->sinceTooltip()
					->toggleable(),
				TextColumn::make('deleted_at')
					->dateTime()
					->sortable()
					->sinceTooltip()
					->toggleable(isToggledHiddenByDefault: true),
			])
			->filters([
				TrashedFilter::make()
					->preload()
					->searchable()
					->native(false),
			])
			->defaultSort('name', 'asc')
			->recordAction(null)
			->recordUrl(null)
			->recordActions([
				ActionGroup::make([
					ViewAction::make(),
					EditAction::make(),
					DeleteAction::make(),
					ForceDeleteAction::make(),
					RestoreAction::make(),
				])
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
