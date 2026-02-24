<?php

/*
 * Project Name: personal-v4
 * File: AllocateFundAction.php
 * Created Date: Tuesday February 24th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\PaymentGoals\Actions;

use App\Models\PaymentGoal;
use Filament\Actions\Action;

class AllocateFundAction
{
	public static function make(): Action
	{
		return Action::make('allocate_fund')
			->label('Allocate Fund')
			->icon('heroicon-o-banknotes')
			->color('success')
			->visible(fn(PaymentGoal $record) => $record->progress_percent < 100)
			->url(fn(PaymentGoal $record): string => \App\Filament\Resources\PaymentGoals\PaymentGoalResource::getUrl('allocate-fund', ['record' => $record]));
	}
}
