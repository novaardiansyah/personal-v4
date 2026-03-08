<?php

/*
 * Project Name: personal-v4
 * File: PaymentCategoryInfolist.php
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

use App\Models\PaymentCategory;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentCategoryInfolist
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				TextEntry::make('user.name')
					->label('User')
					->placeholder('-'),
				TextEntry::make('name'),
				TextEntry::make('deleted_at')
					->dateTime()
					->visible(fn(PaymentCategory $record): bool => $record->trashed()),
				TextEntry::make('created_at')
					->dateTime()
					->placeholder('-'),
				TextEntry::make('updated_at')
					->dateTime()
					->placeholder('-'),
			]);
	}
}
