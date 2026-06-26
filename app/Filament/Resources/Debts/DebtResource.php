<?php

/*
 * Project Name: personal-v4
 * File: DebtResource.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Debts;

use App\Filament\Resources\Debts\Pages\CreateDebt;
use App\Filament\Resources\Debts\Pages\EditDebt;
use App\Filament\Resources\Debts\Pages\ListDebts;
use App\Filament\Resources\Debts\Pages\ViewDebt;
use App\Filament\Resources\Debts\Schemas\DebtForm;
use App\Filament\Resources\Debts\Schemas\DebtInfolist;
use App\Filament\Resources\Debts\Tables\DebtsTable;
use App\Filament\Resources\Debts\RelationManagers\InstallmentsRelationManager;
use App\Models\Debt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DebtResource extends Resource
{
	protected static ?string $model = Debt::class;

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

	protected static string|UnitEnum|null $navigationGroup = 'Payments';

	protected static ?int $navigationSort = 10;
	
	protected static ?string $recordTitleAttribute = 'name';

	public static function form(Schema $schema): Schema
	{
		return DebtForm::configure($schema);
	}

	public static function infolist(Schema $schema): Schema
	{
		return DebtInfolist::configure($schema);
	}

	public static function table(Table $table): Table
	{
		return DebtsTable::configure($table);
	}

	public static function getRelations(): array
	{
		return [
			InstallmentsRelationManager::class,
		];
	}

	public static function getPages(): array
	{
		return [
			'index' => ListDebts::route('/'),
			'create' => CreateDebt::route('/create'),
			'view' => ViewDebt::route('/{record}'),
			'edit' => EditDebt::route('/{record}/edit'),
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
