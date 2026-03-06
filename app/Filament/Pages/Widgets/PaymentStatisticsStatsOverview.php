<?php

/*
 * Project Name: personal-v4
 * File: PaymentStatisticsStatsOverview.php
 * Created Date: Friday March 6th 2026
 * 
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 * 
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Filament\Pages\Widgets;

use App\Models\Payment;
use App\Models\Setting;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PaymentStatisticsStatsOverview extends StatsOverviewWidget
{
	protected function getStats(): array
	{
		$paymentsModel = new Payment();
		$overview      = $paymentsModel->overviewReport();

		$payments    = $overview['payments'];
		$month_str   = $overview['month_str'];
		$thisWeek    = $overview['thisWeek'];
		$total_saldo = $overview['total_saldo'];

		$scheduled_expense = $payments->scheduled_expense ?? 0;
		$scheduled_income  = $payments->scheduled_income ?? 0;

		$totalAfterScheduledExpense = $total_saldo + $scheduled_income - $scheduled_expense;

		return [
			Stat::make('Income (' . $month_str . ')', toIndonesianCurrency($payments->all_income ?? 0, showCurrency: Setting::showPaymentCurrency()))
				->description(toIndonesianCurrency($scheduled_income, showCurrency: Setting::showPaymentCurrency()) . ' scheduled income')
				->descriptionIcon('heroicon-m-arrow-trending-up')
				->descriptionColor('success'),
			Stat::make('Expense (' . $month_str . ')', toIndonesianCurrency($payments->all_expense ?? 0, showCurrency: Setting::showPaymentCurrency()))
				->description(toIndonesianCurrency($scheduled_expense, showCurrency: Setting::showPaymentCurrency()) . ' scheduled expense')
				->descriptionIcon('heroicon-m-arrow-trending-down')
				->descriptionColor('danger'),
			Stat::make('Total Deposit', toIndonesianCurrency($total_saldo, showCurrency: Setting::showPaymentCurrency()))
				->description(toIndonesianCurrency($totalAfterScheduledExpense, showCurrency: Setting::showPaymentCurrency()) . ' remaining scheduled')
				->descriptionIcon('heroicon-m-credit-card')
				->descriptionColor('primary'),
			Stat::make('Daily Expense (Today)', toIndonesianCurrency($payments->daily_expense ?? 0, showCurrency: Setting::showPaymentCurrency()))
				->description(toIndonesianCurrency($payments->daily_income ?? 0, showCurrency: Setting::showPaymentCurrency()) . ' daily income')
				->descriptionIcon('heroicon-m-calendar')
				->descriptionColor('warning'),
			Stat::make('Weekly Expense (' . $thisWeek . ')', toIndonesianCurrency($payments->weekly_expense ?? 0, showCurrency: Setting::showPaymentCurrency()))
				->description(toIndonesianCurrency($payments->avg_weekly_expense ?? 0, showCurrency: Setting::showPaymentCurrency()) . ' avg weekly expense')
				->descriptionIcon('heroicon-m-chart-bar')
				->descriptionColor('info'),
			Stat::make('Avg Daily Expense (' . $month_str . ')', toIndonesianCurrency($payments->avg_daily_expense ?? 0, showCurrency: Setting::showPaymentCurrency()))
				->description('Based on ' . Carbon::now()->day . ' days elapsed')
				->descriptionIcon('heroicon-m-calculator')
				->descriptionColor('warning'),
		];
	}
}
