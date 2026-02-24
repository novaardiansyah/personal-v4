<?php

namespace App\Jobs\PaymentGoalResource;

use App\Models\PaymentGoal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

class PaymentGoalReportPdf implements ShouldQueue
{
	use Queueable;

	public function __construct(public array $data = []) {}

	public function handle(): void
	{
		Log::info('4200 --> PaymentGoalReportPdf: Started.');

		$user = $this->data['user'] ?? getUser();
		$now = Carbon::now()->toDateTimeString();

		$goals = $this->getGoals();

		$totalTarget = $goals->sum('target_amount');
		$totalAmount = $goals->sum('amount');
		$completedCount = $goals->filter(fn($g) => $g->progress_percent >= 100)->count();
		$activeCount = $goals->filter(fn($g) => $g->progress_percent < 100)->count();

		Carbon::setLocale('id');

		$title = 'Laporan Target Pembayaran';
		$reportType = match ($this->data['status'] ?? 'all') {
			'active' => 'Target Aktif',
			'completed' => 'Target Selesai',
			'date_range' => 'Custom Date Range',
			default => 'Semua Target',
		};

		$periode = $reportType;
		if (isset($this->data['start_date']) && isset($this->data['end_date'])) {
			$start = Carbon::parse($this->data['start_date']);
			$end = Carbon::parse($this->data['end_date']);
			$periode = $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y');
		}

		$mpdf = new Mpdf();
		$mpdf->WriteHTML(view('payment-goal-resource.make-pdf.header', [
			'title' => $title,
			'now' => carbonTranslatedFormat($now, 'l, d M Y, H.i', 'id') . ' WIB',
			'periode' => $periode,
			'user' => $user,
		])->render());

		$rowIndex = 1;
		$goals->chunk(200)->each(function ($list) use ($mpdf, &$rowIndex) {
			foreach ($list as $record) {
				$view = view('payment-goal-resource.make-pdf.body', [
					'record' => $record,
					'loopIndex' => $rowIndex++,
				])->render();
				$mpdf->WriteHTML($view);
			}
		});

		$mpdf->WriteHTML(view('payment-goal-resource.make-pdf.footer', [
			'total_target' => $totalTarget,
			'total_amount' => $totalAmount,
			'completed_count' => $completedCount,
			'active_count' => $activeCount,
		])->render());

		$result = $this->savePdf($mpdf, $user);

		Log::info('4201 --> PaymentGoalReportPdf: Finished.');
	}

	protected function getGoals()
	{
		$query = PaymentGoal::query();

		$status = $this->data['status'] ?? 'all';

		match ($status) {
			'active' => $query->where('status_id', '!=', 3),
			'completed' => $query->where('status_id', 3),
			'date_range' => $query->whereBetween('created_at', [
				$this->data['start_date'] ?? Carbon::now()->startOfYear(),
				$this->data['end_date'] ?? Carbon::now()->endOfYear(),
			]),
			default => $query,
		};

		return $query->orderBy('target_date', 'desc')->get();
	}

	protected function savePdf(Mpdf $mpdf, $user): array
	{
		$extension = 'pdf';
		$directory = 'public/attachments';
		$filenameWithoutExtension = \Illuminate\Support\Str::orderedUuid()->toString();
		$filename = "{$filenameWithoutExtension}.{$extension}";
		$filepath = "{$directory}/{$filename}";
		$fullPath = storage_path("app/{$filepath}");

		if (!file_exists(dirname($fullPath))) {
			mkdir(dirname($fullPath), 0755, true);
		}

		$mpdf->Output($fullPath, \Mpdf\Output\Destination::FILE);

		$expiration = now()->addMonth();
		$fileUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
			'download',
			$expiration,
			['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => $directory]
		);

		$notification = $this->data['notification'] ?? false;

		if ($notification) {
			\Filament\Notifications\Notification::make()
				->title('PDF file ready')
				->body('Your payment goal report is ready to download')
				->icon('heroicon-o-arrow-down-tray')
				->iconColor('success')
				->actions([
					\Filament\Actions\Action::make('download')
						->label('Download')
						->url($fileUrl)
						->openUrlInNewTab()
						->markAsRead()
						->button()
				])
				->sendToDatabase($user);
		}

		$send_to_email = $this->data['send_to_email'] ?? false;

		if ($send_to_email) {
			$this->sendEmail($fullPath, $user);
		}

		return [
			'fullpath' => $fullPath,
			'url' => $fileUrl,
		];
	}

	protected function sendEmail(string $fullPath, $user): void
	{
		$email = getSetting('custom_payment_email');
		$authorName = getSetting('author_name');

		$data = [
			'log_name' => 'payment_goal_notification',
			'email' => $email,
			'author_name' => $authorName,
			'subject' => 'Notifikasi: Laporan Target Pembayaran',
			'created_at' => now()->toDateTimeString(),
			'attachments' => [$fullPath],
		];

		$mail = new \App\Mail\PaymentGoalResource\PaymentGoalReportMail($data);
		\Illuminate\Support\Facades\Mail::to($email)->queue($mail);
		$html = $mail->render();

		saveActivityLog([
			'log_name' => 'Notification',
			'description' => 'Payment Goal Report by ' . $user->name,
			'event' => 'Mail Notification',
			'properties' => [
				'email' => $data['email'],
				'subject' => $data['subject'],
				'attachments' => $data['attachments'],
				'html' => $html,
			],
		]);
	}
}
