<?php

/*
 * Project Name: personal-v4
 * File: ReplicateAction.php
 * Created Date: June 01, 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\Payments\Actions;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ReplicateAction
{
	public static function make()
	{
		return Action::make('custom-replicate')
			->label('Replicate')
			->icon(Heroicon::OutlinedClipboardDocument)
			->color('warning')
			->visible(fn(Payment $record): bool => $record->is_draft === true)
			->modalHeading(fn(Payment $record): string => 'Replicate Payment ' . $record->code)
			->modalWidth(Width::Large)
			->schema([
				Textarea::make('name')
					->label('Notes')
					->nullable()
					->rows(3),

				Repeater::make('dates')
					->label('Dates')
					->required()
					->minItems(1)
					->reorderable(false)
					->schema([
						DatePicker::make('date')
							->label('Date')
							->required()
							->displayFormat('M d, Y')
							->closeOnDateSelection()
							->native(false),
					]),
			])
			->fillForm(function (Payment $record): array {
				return [
					'dates' => [
						['date' => Carbon::parse($record->date)->addDay()->toDateString()],
					],
					'name' => $record->name,
				];
			})
			->action(function (Payment $record, array $data): void {
				$count = 0;

				foreach ($data['dates'] as $dateEntry) {
					$replicated = $record->replicate();

					$replicated->date = $dateEntry['date'];
					$replicated->name = $data['name'];
					$replicated->save();

					foreach ($record->items as $item) {
						$replicated->items()->attach($item->id, [
							'item_code' => $item->pivot->item_code,
							'quantity'  => $item->pivot->quantity,
							'price'     => $item->pivot->price,
							'total'     => $item->pivot->total,
						]);
					}

					$count++;
				}

				Notification::make()
					->success()
					->title('Payment Replicated')
					->body("Payment has been replicated x{$count} successfully.")
					->send();
			});
	}
}
