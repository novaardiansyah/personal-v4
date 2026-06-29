<?php

/*
 * Project Name: personal-v4
 * File: DebtInstallmentResource.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\DebtInstallments;

use App\Filament\Resources\DebtInstallments\Pages\EditDebtInstallment;
use App\Filament\Resources\DebtInstallments\Pages\ListDebtInstallments;
use App\Filament\Resources\DebtInstallments\Pages\ViewDebtInstallment;
use App\Filament\Resources\DebtInstallments\Schemas\DebtInstallmentForm;
use App\Filament\Resources\DebtInstallments\Schemas\DebtInstallmentInfolist;
use App\Filament\Resources\DebtInstallments\Tables\DebtInstallmentsTable;
use App\Models\DebtInstallment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DebtInstallmentResource extends Resource
{
  protected static ?string $model = DebtInstallment::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyBangladeshi;

  protected static string|UnitEnum|null $navigationGroup = 'Payments';

  protected static ?string $navigationParentItem = 'Debts';

  protected static ?int $navigationSort = 11;

  protected static ?string $recordTitleAttribute = 'debt.name';

  public static function canEdit(Model $record): bool
  {
    return $record->status !== 'paid';
  }

  public static function form(Schema $schema): Schema
  {
    return DebtInstallmentForm::configure($schema);
  }

  public static function infolist(Schema $schema): Schema
  {
    return DebtInstallmentInfolist::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return DebtInstallmentsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListDebtInstallments::route('/'),
      'view' => ViewDebtInstallment::route('/{record}'),
      'edit' => EditDebtInstallment::route('/{record}/edit'),
    ];
  }
}
