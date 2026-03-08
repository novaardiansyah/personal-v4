<?php

/*
 * Project Name: personal-v4
 * File: CreatePaymentCategory.php
 * Created Date: Monday March 9th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\PaymentCategories\Pages;

use App\Filament\Resources\PaymentCategories\PaymentCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentCategory extends CreateRecord
{
	protected static string $resource = PaymentCategoryResource::class;
}
