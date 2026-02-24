<?php

/*
 * Project Name: personal-v4
 * File: PaymentGoalAction.php
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
use App\Jobs\PaymentGoalResource\PaymentGoalReportPdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class PaymentGoalAction
{
	public static function printPdfSchema(Schema $schema): Schema
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

	public static function printPdfAction(Action $action, array $data): void
	{
		$user = getUser();
		$send_to_email = $data['send_to_email'] ?? false;

		$sendTo = [
			'send_to_email' => $send_to_email,
			'user' => $user,
			'notification' => true,
		];

		match ($data['report_type']) {
			'active' => PaymentGoalReportPdf::dispatch(array_merge($sendTo, ['status' => 'active'])),
			'completed' => PaymentGoalReportPdf::dispatch(array_merge($sendTo, ['status' => 'completed'])),
			'date_range' => PaymentGoalReportPdf::dispatch(array_merge($sendTo, [
				'start_date' => $data['start_date'],
				'end_date' => $data['end_date'],
			])),
			default => PaymentGoalReportPdf::dispatch(array_merge($sendTo, ['status' => 'all'])),
		};

		$messages = [
			'all' => 'All goals PDF report will be sent to your email.',
			'active' => 'Active goals PDF report will be sent to your email.',
			'completed' => 'Completed goals PDF report will be sent to your email.',
			'date_range' => 'Custom PDF report will be sent to your email.',
		];

		if (!$send_to_email) {
			$data['report_type'] = 'default';
		}

		$action->success();
		$action->successNotification(
			Notification::make()
				->title('PDF report in process')
				->body($messages[$data['report_type']])
				->success()
		);
	}

	public static function printPdf()
	{
		return Action::make('print_pdf')
			->label('PDF')
			->color('primary')
			->icon('heroicon-o-printer')
			->modalHeading('Generate PDF Report')
			->modalDescription('Select report type and configure options.')
			->modalWidth(Width::Medium)
			->schema(fn(Schema $form): Schema => self::printPdfSchema($form))
			->action(fn(Action $action, array $data) => self::printPdfAction($action, $data));
	}

	public static function printExcelSchema(Schema $schema): Schema
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

	public static function printExcelAction(Action $action, array $data): void
	{
		$user = getUser();
		$send_to_email = $data['send_to_email'] ?? false;

		$sendTo = [
			'send_to_email' => $send_to_email,
			'user' => $user,
			'notification' => true,
		];

		match ($data['report_type']) {
			'active' => PaymentGoalReportExcelJob::dispatch(array_merge($sendTo, ['status' => 'active'])),
			'completed' => PaymentGoalReportExcelJob::dispatch(array_merge($sendTo, ['status' => 'completed'])),
			'date_range' => PaymentGoalReportExcelJob::dispatch(array_merge($sendTo, [
				'start_date' => $data['start_date'],
				'end_date' => $data['end_date'],
			])),
			default => PaymentGoalReportExcelJob::dispatch(array_merge($sendTo, ['status' => 'all'])),
		};

		$messages = [
			'all' => 'All goals Excel report will be sent to your email.',
			'active' => 'Active goals Excel report will be sent to your email.',
			'completed' => 'Completed goals Excel report will be sent to your email.',
			'date_range' => 'Custom Excel report will be sent to your email.',
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

	public static function printExcel()
	{
		return Action::make('print_excel')
			->label('Excel')
			->color('primary')
			->icon('heroicon-o-printer')
			->modalHeading('Generate Excel Report')
			->modalDescription('Select report type and configure options.')
			->modalWidth(Width::Medium)
			->schema(fn(Schema $form): Schema => self::printExcelSchema($form))
			->action(fn(Action $action, array $data) => self::printExcelAction($action, $data));
	}
}
