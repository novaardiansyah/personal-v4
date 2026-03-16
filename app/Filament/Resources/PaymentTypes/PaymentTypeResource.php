<?php

/*
 * Project Name: personal-v4
 * File: PaymentTypeResource.php
 * Created Date: Thursday December 11th 2025
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\PaymentTypes;

use BackedEnum;
use UnitEnum;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\Width;
use App\Filament\Resources\PaymentTypes\Pages\ManagePaymentTypes;
use App\Models\Payment;
use App\Models\PaymentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentTypeResource extends Resource
{
	protected static ?string $model = PaymentType::class;

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

	protected static string|UnitEnum|null $navigationGroup = 'Payments';

	protected static ?string $navigationParentItem = 'Payments';

	protected static ?int $navigationSort = 100;

	protected static ?string $recordTitleAttribute = 'name';

	public static function form(Schema $schema): Schema
	{
		return $schema
			->components([
				TextInput::make('uid')
					->label('UID')
					->disabled()
					->placeholder('Auto generated')
					->copyable(),

				TextInput::make('name')
					->label('Name')
					->required(),
			])
			->columns(1);
	}

	public static function infolist(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make([
					TextEntry::make('uid')
						->label('UID')
						->badge()
						->copyable()
						->tooltip(fn(PaymentType $record): string => $record->uid ?? ''),
					TextEntry::make('name')
						->label('Name'),
				])
				->description('General information')
				->columns(2),

				Section::make([
					TextEntry::make('created_at')
						->sinceTooltip()
						->dateTime(),
					TextEntry::make('updated_at')
						->sinceTooltip()
						->dateTime(),
					TextEntry::make('deleted_at')
						->sinceTooltip()
						->dateTime(),
				])
				->description('Timestamp information')
				->columns(3),
			])
			->columns(1);
	}

	public static function table(Table $table): Table
	{
		return $table
			->recordTitleAttribute('name')
			->columns([
				TextColumn::make('index')
					->label('#')
					->rowIndex(),
				TextColumn::make('uid')
					->label('UID')
					->badge()
					->limit(13)
					->searchable()
					->copyable()
					->tooltip(fn(PaymentType $record): string => $record->uid ?? '')
					->copyableState(fn(PaymentType $record): string => $record->uid ?? ''),
				TextColumn::make('name')
					->searchable(),
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
					->toggleable(),
			])
			->filters([
				TrashedFilter::make()
					->preload()
					->searchable()
					->native(false),
			])
			->recordAction(null)
			->recordUrl(null)
			->recordActions([
				ActionGroup::make([
					ViewAction::make()
						->modalHeading('View details')
						->slideOver()
						->modalWidth(Width::ThreeExtraLarge),

					EditAction::make()
						->modalWidth(Width::Medium),

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

	public static function getPages(): array
	{
		return [
			'index' => ManagePaymentTypes::route('/'),
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
