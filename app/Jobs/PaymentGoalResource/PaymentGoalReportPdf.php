<?php

/*
 * Project Name: personal-v4
 * File: PaymentGoalReportPdf.php
 * Created Date: Tuesday February 24th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Jobs\PaymentGoalResource;

use App\Models\PaymentGoal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use Illuminate\Support\Str;
use Mpdf\Output\Destination as MpdfDestination;
use Illuminate\Support\Facades\URL;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Actions\Action as FilamentAction;
use App\Enums\EmailStatus;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\File;
use App\Services\EmailResource\EmailService;

class PaymentGoalReportPdf implements ShouldQueue
{
	use Queueable;

	public function __construct(public array $data = []) {}

	public function handle(): void
	{
		Log::info('4200 --> PaymentGoalReportPdf: Started.');

		$user = $this->data['user'] ?? getUser();
		$now  = Carbon::now()->toDateTimeString();

		$goals = $this->getGoals();

		$totalTarget    = $goals->sum('target_amount');
		$totalAmount    = $goals->sum('amount');
		$completedCount = $goals->filter(fn($g) => $g->progress_percent >= 100)->count();
		$activeCount    = $goals->filter(fn($g) => $g->progress_percent < 100)->count();

		Carbon::setLocale('id');

		$title = 'Laporan Target Pembayaran';
		$reportType = match ($this->data['status'] ?? 'all') {
			'active'     => 'Target Aktif',
			'completed'  => 'Target Selesai',
			'date_range' => 'Custom Date Range',
			default      => 'Semua Target',
		};

		$periode = $reportType;
		if (isset($this->data['start_date']) && isset($this->data['end_date'])) {
			$start   = Carbon::parse($this->data['start_date']);
			$end     = Carbon::parse($this->data['end_date']);
			$periode = $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y');
		}

		$mpdf = new Mpdf();
		$mpdf->WriteHTML(view('payment-goal-resource.make-pdf.header', [
			'title'   => $title,
			'now'     => carbonTranslatedFormat($now, 'l, d M Y, H.i', 'id') . ' WIB',
			'periode' => $periode,
			'user'    => $user,
		])->render());

		$rowIndex = 1;
		$goals->chunk(200)->each(function ($list) use ($mpdf, &$rowIndex) {
			foreach ($list as $record) {
				$view = view('payment-goal-resource.make-pdf.body', [
					'record'    => $record,
					'loopIndex' => $rowIndex++,
				])->render();
				$mpdf->WriteHTML($view);
			}
		});

		$mpdf->WriteHTML(view('payment-goal-resource.make-pdf.footer', [
			'total_target'    => $totalTarget,
			'total_amount'    => $totalAmount,
			'completed_count' => $completedCount,
			'active_count'    => $activeCount,
		])->render());

		$this->savePdf($mpdf, $user);

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
		$extension                = 'pdf';
		$directory                = 'public/attachments';
		$filenameWithoutExtension = Str::orderedUuid()->toString();
		$filename                 = "{$filenameWithoutExtension}.{$extension}";
		$filepath                 = "{$directory}/{$filename}";
		$fullPath                 = storage_path("app/{$filepath}");

		if (!file_exists(dirname($fullPath))) {
			mkdir(dirname($fullPath), 0755, true);
		}

		$mpdf->Output($fullPath, MpdfDestination::FILE);

		$expiration = now()->addMonth();
		$fileUrl = URL::temporarySignedRoute(
			'download',
			$expiration,
			['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => $directory]
		);

		$notification = $this->data['notification'] ?? false;

		if ($notification) {
			FilamentNotification::make()
				->title('PDF file ready')
				->body('Your payment goal report is ready to download')
				->icon('heroicon-o-arrow-down-tray')
				->iconColor('success')
				->actions([
					FilamentAction::make('download')
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
			'url'      => $fileUrl,
		];
	}

	protected function sendEmail(string $fullPath, $user): void
	{
		$email      = getSetting('custom_payment_email');
		$authorName = getSetting('author_name');
		$now        = Carbon::now()->toDateTimeString();

		$template = EmailTemplate::where('alias', 'payment_goals_report')->first();

		if ($template) {
			$placeholders = array_merge($template->placeholders ?? [], [
				'now' => carbonTranslatedFormat($now, 'd M Y, H.i', 'id'),
			]);

			$message = $template->message;
			foreach ($placeholders as $key => $value) {
				$message = str_replace('{' . $key . '}', (string) $value, $message);
			}

			$emailModel = Email::create([
				'name'    => $authorName,
				'email'   => $email,
				'subject' => $template->subject . ' (' . carbonTranslatedFormat($now, 'd M Y, H.i', 'id') . ')',
				'message' => $message,
				'status'  => EmailStatus::Draft,
			]);

			$relativePath = str_replace(storage_path('app/public/'), '', $fullPath);

			File::create([
				'user_id'      => $user->id,
				'file_name'    => basename($fullPath),
				'file_path'    => $relativePath,
				'subject_type' => Email::class,
				'subject_id'   => $emailModel->id,
			]);

			(new EmailService())->sendOrPreview($emailModel, false, [
				'description' => 'Payment Goal Report by ' . $user->name,
			]);
		}
	}
}
