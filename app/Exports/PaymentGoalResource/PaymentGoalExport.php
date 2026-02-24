<?php

namespace App\Exports\PaymentGoalResource;

use App\Models\PaymentGoal;
use Illuminate\Support\Carbon;
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

class PaymentGoalExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting, WithTitle
{
	protected string $status;
	protected ?string $startDate;
	protected ?string $endDate;
	protected int $rowIndex = 0;

	public function __construct(string $status = 'all', ?string $startDate = null, ?string $endDate = null)
	{
		$this->status = $status;
		$this->startDate = $startDate;
		$this->endDate = $endDate;
	}

	public function collection()
	{
		$query = PaymentGoal::query();

		match ($this->status) {
			'active' => $query->where('status_id', '!=', 3),
			'completed' => $query->where('status_id', 3),
			'date_range' => $query->whereBetween('created_at', [
				$this->startDate ?? Carbon::now()->startOfYear(),
				$this->endDate ?? Carbon::now()->endOfYear(),
			]),
			default => $query,
		};

		return $query->orderBy('created_at', 'desc')->get();
	}

	public function headings(): array
	{
		return ['#', 'ID Target', 'Nama', 'Status', 'Target Amount', 'Current Amount', 'Progress (%)', 'Start Date', 'Target Date'];
	}

	public function map($goal): array
	{
		$this->rowIndex++;

		return [
			$this->rowIndex,
			$goal->code,
			$goal->name,
			$goal->status->name ?? '-',
			$goal->target_amount,
			$goal->amount,
			$goal->latest_progress_percent,
			Carbon::parse($goal->start_date)->format('d M Y'),
			Carbon::parse($goal->target_date)->format('d M Y'),
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
				'startColor' => ['rgb' => '155dfc'],
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
					'color' => ['rgb' => '061E29'],
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

		$sheet->getStyle("E2:F{$lastRow}")->applyFromArray([
			'alignment' => [
				'horizontal' => Alignment::HORIZONTAL_RIGHT,
			],
		]);

		return [];
	}

	public function columnFormats(): array
	{
		return [
			'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
			'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
			'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
		];
	}

	public function title(): string
	{
		Carbon::setLocale('id');

		$title = match ($this->status) {
			'active' => 'Target Aktif',
			'completed' => 'Target Selesai',
			'date_range' => 'Custom Date Range',
			default => 'Semua Target',
		};

		if ($this->startDate && $this->endDate) {
			$start = Carbon::parse($this->startDate);
			$end = Carbon::parse($this->endDate);
			$title = $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y');
		}

		return $title;
	}
}
