<?php

namespace App\Filament\Resources\Settings\Actions;

use App\Models\Setting;
use Filament\Actions\Action;

class ReplicateAction
{
	public static function make(): Action
	{
		return Action::make('replicate')
			->label('Replicate')
			->icon('heroicon-s-document-duplicate')
			->color('warning')
			->action(function (Setting $record, Action $action) {
				$newRecord = $record->replicate();
				$newRecord->key .=  '_copy';
				$newRecord->name .=  ' (Copy)';

				$newRecord->save();

				$action->success();
				$action->successNotificationTitle('Setting replicated successfully');
			})
			->requiresConfirmation()
			->modalHeading('Replicate Setting')
			->modalDescription('Are you sure you want to replicate this setting?');
	}
}
