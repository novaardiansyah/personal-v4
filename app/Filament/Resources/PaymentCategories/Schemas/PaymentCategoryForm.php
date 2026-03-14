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

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentCategoryForm
{
	public static function configure(Schema $schema): Schema
	{
		return $schema
			->components([
				Section::make([
					TextInput::make('code')
						->label('Category ID')
						->readOnly()
						->saved(false)
						->copyable()
						->visibleOn('edit'),

					Select::make('user_id')
						->label('Owner')
						->relationship('user', 'name')
						->getOptionLabelFromRecordUsing(function(User $record): string {
							return $record->name . ' (' . $record->email . ')';
						})
						->searchable()
						->preload()
						->native(false)
						->required()
						->default(getUser()->id),

					Textarea::make('name')
						->label('Category')
						->required()
						->rows(3)
						->maxLength(255),

					Toggle::make('is_default')
						->label('Default')
						->helperText('Set as default category for new payments.')
						->default(false),
				])
			])
			->columns(2);
	}
}
