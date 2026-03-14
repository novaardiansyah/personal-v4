<?php

/*
 * Project Name: personal-v4
 * File: ListPayments.php
 * Created Date: Thursday December 11th 2025
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\PaymentType;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListPayments extends ListRecords
{
  protected static string $resource = PaymentResource::class;

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }

  public function getTabs(): array
  {
    return [
      'All' => Tab::make(),
      'Expenses' => Tab::make()
        ->modifyQueryUsing(fn ($query) => $query->where('type_id', PaymentType::EXPENSE)),
      'Income' => Tab::make()
        ->modifyQueryUsing(fn ($query) => $query->where('type_id', PaymentType::INCOME)),
      'Transfer' => Tab::make()
        ->modifyQueryUsing(fn ($query) => $query->where('type_id', PaymentType::TRANSFER)),
      'Withdrawal' => Tab::make()
        ->modifyQueryUsing(fn ($query) => $query->where('type_id', PaymentType::WITHDRAWAL)),
      'Scheduled' => Tab::make()
        ->modifyQueryUsing(fn ($query) => $query->where('is_scheduled', true)),
    ];
  }
}