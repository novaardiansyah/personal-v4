<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Widgets\PaymentStatisticsAccountOverview;
use App\Filament\Pages\Widgets\PaymentStatisticsStatsOverview;
use App\Filament\Widgets\PaymentCategoryChartWidget;
use App\Filament\Widgets\PaymentChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
	public function getWidgets(): array
	{
		return [
			PaymentStatisticsStatsOverview::class,
			PaymentStatisticsAccountOverview::class,
			PaymentCategoryChartWidget::class,
			PaymentChartWidget::class,
		];
	}

	public function getColumns(): int|array
	{
		return 2;
	}
}
