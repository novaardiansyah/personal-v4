<?php

namespace App\Exports\PaymentResource;

use App\Models\Payment;
use App\Models\PaymentType;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting, WithTitle
{
  protected string $startDate;
  protected string $endDate;
  protected ?int $paymentAccountId;
  protected int $rowIndex = 0;

  public function __construct(string $startDate, string $endDate, ?int $paymentAccountId = null)
  {
    $this->startDate = $startDate;
    $this->endDate = $endDate;
    $this->paymentAccountId = $paymentAccountId;
  }

  public function collection()
  {
    return Payment::whereBetween('date', [$this->startDate, $this->endDate])
      ->when($this->paymentAccountId, function ($query) {
        $query->where('payment_account_id', $this->paymentAccountId);
      })
      ->orderBy('date', 'desc')
      ->get();
  }

  public function headings(): array
  {
    return [
      '#',
      'Transaction ID',
      'Date',
      'Notes',
      'Payment Account',
      'Type',
      'Transfer/Other',
      'Income',
      'Expense',
    ];
  }

  public function map($payment): array
  {
    $this->rowIndex++;

    $income = (int) $payment->type_id === PaymentType::INCOME ? $payment->amount : 0;
    $expense = (int) $payment->type_id === PaymentType::EXPENSE ? $payment->amount : 0;
    $transfer = !in_array((int) $payment->type_id, [PaymentType::INCOME, PaymentType::EXPENSE]) ? $payment->amount : 0;

    return [
      $this->rowIndex,
      $payment->code,
      Carbon::parse($payment->date)->format('d M Y'),
      $payment->name ?? '-',
      $payment->payment_account?->name ?? '-',
      $payment->payment_type?->name ?? '-',
      $transfer,
      $income,
      $expense,
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    $lastRow = $sheet->getHighestRow();
    $lastColumn = 'I';

    $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
      'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
      ],
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4F46E5'],
      ],
      'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
      ],
    ]);

    $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
      'borders' => [
        'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
          'color' => ['rgb' => 'D1D5DB'],
        ],
      ],
    ]);

    $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
      'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
      ],
    ]);

    $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
      'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
      ],
    ]);

    return [];
  }

  public function columnFormats(): array
  {
    return [
      'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
      'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
      'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
    ];
  }

  public function title(): string
  {
    Carbon::setLocale('id');
    $carbonStart = Carbon::parse($this->startDate);
    $carbonEnd = Carbon::parse($this->endDate);

    if ($carbonStart->isSameDay($carbonEnd)) {
      return $carbonStart->translatedFormat('d F Y');
    }

    return $carbonStart->translatedFormat('d M Y') . ' - ' . $carbonEnd->translatedFormat('d M Y');
  }
}
