<?php

/*
 * Project Name: personal-v4
 * File: ListDebtInstallments.php
 * Created Date: Thursday June 25th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\DebtInstallments\Pages;

use App\Filament\Resources\DebtInstallments\DebtInstallmentResource;
use Filament\Resources\Pages\ListRecords;

class ListDebtInstallments extends ListRecords
{
  protected static string $resource = DebtInstallmentResource::class;

  protected function getHeaderActions(): array
  {
    return [];
  }
}
