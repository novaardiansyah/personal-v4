<?php

/*
 * Project Name: personal-v4
 * File: PaymentCategoryResource.php
 * Created Date: Monday March 9th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\PaymentCategories;

use App\Filament\Resources\PaymentCategories\Pages\CreatePaymentCategory;
use App\Filament\Resources\PaymentCategories\Pages\EditPaymentCategory;
use App\Filament\Resources\PaymentCategories\Pages\ListPaymentCategories;
use App\Filament\Resources\PaymentCategories\Pages\ViewPaymentCategory;
use App\Filament\Resources\PaymentCategories\Schemas\PaymentCategoryForm;
use App\Filament\Resources\PaymentCategories\Schemas\PaymentCategoryInfolist;
use App\Filament\Resources\PaymentCategories\Tables\PaymentCategoriesTable;
use App\Models\PaymentCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PaymentCategoryResource extends Resource
{
	protected static ?string $model = PaymentCategory::class;

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

	protected static ?string $recordTitleAttribute = 'name';

	protected static ?int $navigationSort = 20;

	protected static string|UnitEnum|null $navigationGroup = 'Payments';

	protected static ?string $navigationParentItem = 'Payments';

	public static function form(Schema $schema): Schema
	{
		return PaymentCategoryForm::configure($schema);
	}

	public static function infolist(Schema $schema): Schema
	{
		return PaymentCategoryInfolist::configure($schema);
	}

	public static function table(Table $table): Table
	{
		return PaymentCategoriesTable::configure($table);
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
			'index'  => ListPaymentCategories::route('/'),
			'create' => CreatePaymentCategory::route('/create'),
			'view'   => ViewPaymentCategory::route('/{record}'),
			'edit'   => EditPaymentCategory::route('/{record}/edit'),
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
