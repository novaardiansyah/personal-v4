<?php

namespace App\Filament\Resources\Payments\Actions;

use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
				DatePicker::make('date')
					->label('Date')
					->required()
					->displayFormat('M d, Y')
					->closeOnDateSelection()
					->native(false),

				Textarea::make('name')
					->label('Notes')
					->nullable()
					->rows(3),
			])
			->fillForm(function (Payment $record): array {
				return [
					'date' => $record->date,
					'name' => $record->name,
				];
			})
			->action(function (Payment $record, array $data): void {
				$replicated = $record->replicate();

				$replicated->date = $data['date'];
				$replicated->name = $data['name'];
				$replicated->save();

				Notification::make()
					->success()
					->title('Payment Replicated')
					->body('Payment has been replicated successfully.')
					->send();
			});
	}
}
