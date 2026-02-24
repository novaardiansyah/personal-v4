<?php

/*
 * Project Name: personal-v4
 * File: PrintExcelAction.php
 * Created Date: Tuesday February 24th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Resources\PaymentGoals\Actions;

use App\Jobs\PaymentGoalResource\PaymentGoalReportExcelJob;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class PrintExcelAction
{
	public static function make(): Action
	{
		return Action::make('print_excel')
			->label('Excel')
			->color('primary')
			->icon('heroicon-o-printer')
			->modalHeading('Generate Excel Report')
			->modalDescription('Select report type and configure options.')
			->modalWidth(Width::Medium)
			->schema(fn(Schema $form): Schema => self::schema($form))
			->action(fn(Action $action, array $data) => self::action($action, $data));
	}

	private static function schema(Schema $schema): Schema
	{
		return $schema
			->components([
				Select::make('report_type')
					->label('Report Type')
					->options([
						'all' => 'All Goals',
						'active' => 'Active Goals',
						'completed' => 'Completed Goals',
						'date_range' => 'Custom Date Range',
					])
					->default('all')
					->required()
					->live()
					->native(false),
				DatePicker::make('start_date')
					->label('Start Date')
					->required()
					->native(false)
					->default(Carbon::now()->startOfYear())
					->visible(fn($get) => $get('report_type') === 'date_range'),
				DatePicker::make('end_date')
					->label('End Date')
					->required()
					->native(false)
					->default(Carbon::now()->endOfYear())
					->visible(fn($get) => $get('report_type') === 'date_range'),
				Toggle::make('send_to_email')
					->label('Send to Email')
					->default(true),
			]);
	}

	private static function action(Action $action, array $data): void
	{
		$user = getUser();
		$send_to_email = $data['send_to_email'] ?? false;

		$sendTo = [
			'send_to_email' => $send_to_email,
			'user' => $user,
			'notification' => true,
		];

		$params = match ($data['report_type']) {
			'active'     => ['status' => 'active'],
			'completed'  => ['status' => 'completed'],
			'date_range' => [
				'start_date' => $data['start_date'],
				'end_date'   => $data['end_date'],
			],
			default      => ['status' => 'all'],
		};

		PaymentGoalReportExcelJob::dispatch(array_merge($sendTo, $params));

		$messages = [
			'all' => 'All goals Excel report will be sent to your email.',
			'active' => 'Active goals Excel report will be sent to your email.',
			'completed' => 'Completed goals Excel report will be sent to your email.',
			'date_range' => 'Custom Excel report will be sent to your email.',
			'default' => 'Excel report is being generated.',
		];

		if (!$send_to_email) {
			$data['report_type'] = 'default';
		}

		$action->success();
		$action->successNotification(
			Notification::make()
				->title('Excel Report in process')
				->body($messages[$data['report_type']])
				->success()
		);
	}
}
