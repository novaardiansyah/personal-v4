<?php

/*
 * Project Name: personal-v4
 * File: PaymentStatisticsAccountOverview.php
 * Created Date: Friday March 6th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Pages\Widgets;

use App\Models\PaymentAccount;
use Filament\Widgets\ChartWidget;

class PaymentStatisticsAccountOverview extends ChartWidget
{
	protected ?string $heading = 'Account Balances';
	protected int | string | array $columnSpan = 1;
	protected ?string $maxHeight = '400px';

	protected function getData(): array
	{
		$accounts = PaymentAccount::where('user_id', auth()->id())->orderBy('name')->get();

		$labels = [];
		$data = [];
		$colors = [
			'rgba(59, 130, 246, 0.8)',
			'rgba(16, 185, 129, 0.8)',
			'rgba(245, 158, 11, 0.8)',
			'rgba(239, 68, 68, 0.8)',
			'rgba(139, 92, 246, 0.8)',
			'rgba(236, 72, 153, 0.8)',
			'rgba(20, 184, 166, 0.8)',
			'rgba(249, 115, 22, 0.8)',
			'rgba(99, 102, 241, 0.8)',
			'rgba(34, 197, 94, 0.8)',
		];

		foreach ($accounts as $account) {
			$labels[] = $account->name;
			$data[] = $account->deposit ?? 0;
		}

		return [
			'datasets' => [
				[
					'data' => $data,
					'backgroundColor' => array_slice($colors, 0, count($data)),
				],
			],
			'labels' => $labels,
		];
	}

	protected function getType(): string
	{
		return 'pie';
	}
}
