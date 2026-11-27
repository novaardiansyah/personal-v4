<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Payment;
use Illuminate\Support\Carbon;
use Mpdf\Mpdf;

class GenerateFinancialReportPdfTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'generate_financial_report_pdf';
  }

  public function getDescription(): string
  {
    return 'Generate a downloadable PDF financial report for a specific month and year.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'month' => [
          'type'        => 'integer',
          'description' => 'Month (1-12). Defaults to current month.',
        ],
        'year'  => [
          'type'        => 'integer',
          'description' => 'Year (e.g. 2026). Defaults to current year.',
        ],
      ],
      'required'   => [],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $month = !empty($arguments['month']) ? (int) $arguments['month'] : (int) now()->month;
    $year  = !empty($arguments['year']) ? (int) $arguments['year'] : (int) now()->year;

    $payments = Payment::where('user_id', $userId)
      ->whereMonth('date', $month)
      ->whereYear('date', $year)
      ->where('is_draft', false)
      ->with(['payment_type', 'category', 'payment_account'])
      ->orderBy('date')
      ->get();

    $periodLabel  = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
    $totalIncome  = (float) $payments->where('type_id', 2)->sum('amount');
    $totalExpense = (float) $payments->where('type_id', 1)->sum('amount');

    $mpdf = new Mpdf([
      'mode'          => 'utf-8',
      'format'        => 'A4',
      'margin_top'    => 15,
      'margin_bottom' => 15,
      'margin_left'   => 15,
      'margin_right'  => 15,
    ]);

    $rowsHtml = '';
    foreach ($payments as $p) {
      $date     = Carbon::parse($p->date)->format('Y-m-d');
      $name     = e($p->name);
      $type     = e($p->payment_type?->name ?? '-');
      $category = e($p->category?->name ?? '-');
      $account  = e($p->payment_account?->name ?? '-');
      $amount   = e(toIndonesianCurrency((float) $p->amount));

      $rowsHtml .= "
        <tr>
          <td style='padding:6px; border:1px solid #e5e7eb;'>{$date}</td>
          <td style='padding:6px; border:1px solid #e5e7eb;'>{$name}</td>
          <td style='padding:6px; border:1px solid #e5e7eb;'>{$type}</td>
          <td style='padding:6px; border:1px solid #e5e7eb;'>{$category}</td>
          <td style='padding:6px; border:1px solid #e5e7eb;'>{$account}</td>
          <td style='padding:6px; border:1px solid #e5e7eb; text-align:right;'>{$amount}</td>
        </tr>
      ";
    }

    $html = "
      <html>
        <head>
          <style>
            body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
            h1 { font-size: 16px; margin-bottom: 4px; }
            .summary { margin-bottom: 12px; font-size: 11px; }
            table { width: 100%; border-collapse: collapse; font-size: 10px; }
            th { background-color: #f3f4f6; padding: 6px; border: 1px solid #e5e7eb; text-align: left; }
          </style>
        </head>
        <body>
          <h1>Financial Report - {$periodLabel}</h1>
          <div class='summary'>
            Total Income: " . e(toIndonesianCurrency($totalIncome)) . " |
            Total Expense: " . e(toIndonesianCurrency($totalExpense)) . " |
            Net: " . e(toIndonesianCurrency($totalIncome - $totalExpense)) . "
          </div>
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th>Category</th>
                <th>Account</th>
                <th style='text-align:right;'>Amount</th>
              </tr>
            </thead>
            <tbody>
              {$rowsHtml}
    ";

    $mpdf->WriteHTML($html);
    $result = makePdf($mpdf, $user, false, false, true);

    return [
      'status'            => true,
      'period'            => $periodLabel,
      'transaction_count' => $payments->count(),
      'total_income'      => toIndonesianCurrency($totalIncome),
      'total_expense'     => toIndonesianCurrency($totalExpense),
      'filename'          => $result['filename'] ?? null,
      'download_url'      => $result['signed_url'] ?? null,
    ];
  }
}
