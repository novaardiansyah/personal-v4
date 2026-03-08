<?php

/*
 * Project Name: personal-v4
 * File: PaymentCategoryForm.php
 * Created Date: Monday March 9th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Filament\Resources\PaymentCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentCategoryForm
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Select::make('user_id')
					->relationship('user', 'name')
					->default(null),
				TextInput::make('name')
					->required(),
			]);
	}
}
