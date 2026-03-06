<?php

/*
 * Project Name: personal-v4
 * File: PaymentStatistics.php
 * Created Date: Friday March 6th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use App\Filament\Pages\Widgets\PaymentStatisticsAccountOverview;
use App\Filament\Pages\Widgets\PaymentStatisticsStatsOverview;
use App\Filament\Widgets\PaymentChartWidget;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PaymentStatistics extends Page
{
	protected string $view = 'filament.pages.payment-statistics';

	protected static ?string $title = 'Payment Statistics';

	protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

	protected static string|UnitEnum|null $navigationGroup = 'Payments';

	protected static ?string $navigationParentItem = 'Payments';

	protected static ?int $navigationSort = 11;

	protected function getHeaderWidgets(): array
	{
		return [
			PaymentStatisticsStatsOverview::class,
		];
	}

	protected function getFooterWidgets(): array
	{
		return [
			PaymentStatisticsAccountOverview::class,
			PaymentChartWidget::class,
		];
	}

	public function getFooterWidgetsColumns(): int|array
	{
		return 1;
	}
}
