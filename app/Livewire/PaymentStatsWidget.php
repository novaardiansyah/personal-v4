<?php

namespace App\Livewire;

use App\Models\Payment;
use App\Models\Setting;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStatsWidget extends StatsOverviewWidget
{
  protected function getStats(): array
  {
    $paymentsModel = new Payment();
    $overview      = $paymentsModel->overviewReport();

    $payments    = $overview['payments'];
    $month_str   = $overview['month_str'];
    $total_saldo = $overview['total_saldo'];

    $scheduled_expense = $payments->scheduled_expense ?? 0;
    $scheduled_income  = $payments->scheduled_income ?? 0;

    $totalAfterScheduledExpense = $total_saldo + $scheduled_income - $scheduled_expense;

    return [
      Stat::make('Income (' . $month_str . ')', toIndonesianCurrency($payments->all_income, showCurrency: Setting::showPaymentCurrency()))
        ->description(toIndonesianCurrency($scheduled_income, showCurrency: Setting::showPaymentCurrency()) . ' scheduled income')
        ->descriptionIcon('heroicon-m-arrow-trending-up')
        ->descriptionColor('success'),
      Stat::make('Expense (' . $month_str . ')', toIndonesianCurrency($payments->all_expense, showCurrency: Setting::showPaymentCurrency()))
        ->description(toIndonesianCurrency($scheduled_expense, showCurrency: Setting::showPaymentCurrency()) . ' scheduled expense')
        ->descriptionIcon('heroicon-m-arrow-trending-down')
        ->descriptionColor('danger'),
      Stat::make('Total Deposit (' . $month_str . ')', toIndonesianCurrency($total_saldo, showCurrency: Setting::showPaymentCurrency()))
        ->description(toIndonesianCurrency($totalAfterScheduledExpense, showCurrency: Setting::showPaymentCurrency()) . ' remaining scheduled')
        ->descriptionIcon('heroicon-m-credit-card')
        ->descriptionColor('primary'),
    ];
  }
}
