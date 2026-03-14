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

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentCategoryInfolist
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make([
					TextEntry::make('code')
						->label('Category ID')
						->badge()
						->copyable(),
					TextEntry::make('user.name')
						->label('Owner')
						->placeholder('-'),
					TextEntry::make('name')
						->label('Category'),
					IconEntry::make('is_default')
						->label('Default')
						->boolean(),
				])
				->description('General information')
				->collapsible()
				->columns(4),

				Section::make([
					TextEntry::make('created_at')
						->dateTime()
						->sinceTooltip(),
					TextEntry::make('updated_at')
						->dateTime()
						->sinceTooltip(),
					TextEntry::make('deleted_at')
						->dateTime()
						->sinceTooltip()
						->placeholder('-'),
				])
				->description('Timestamp information')
				->collapsible()
				->columns(3),
			])
			->columns(1);
	}
}
