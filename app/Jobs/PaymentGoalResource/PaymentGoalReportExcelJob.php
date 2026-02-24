<?php

namespace App\Jobs\PaymentGoalResource;

use App\Exports\PaymentGoalResource\PaymentGoalExport;
use App\Models\File;
use App\Models\PaymentGoal;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PaymentGoalReportExcelJob implements ShouldQueue
{
	use Queueable;

	public function __construct(public array $data = []) {}

	public function handle(): void
	{
		Log::info('4202 --> PaymentGoalReportExcel: Started.');

		$user = $this->data['user'] ?? getUser();
		$goals = $this->getGoals();

		$extension = 'xlsx';
		$directory = 'public/attachments';
		$filenameWithoutExtension = Str::orderedUuid()->toString();
		$filename = "{$filenameWithoutExtension}.{$extension}";
		$filepath = "{$directory}/{$filename}";

		$status = $this->data['status'] ?? 'all';

		Excel::store(
			new PaymentGoalExport($status, $this->data['start_date'] ?? null, $this->data['end_date'] ?? null),
			"attachments/{$filename}",
			'public'
		);

		$fullPath = storage_path("app/{$filepath}");

		$expiration = now()->addMonth();

		$fileUrl = URL::temporarySignedRoute(
			'download',
			$expiration,
			['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => $directory]
		);

		$notification = $this->data['notification'] ?? false;

		if ($notification) {
			Notification::make()
				->title('Excel file ready')
				->body('Your payment goal report is ready to download')
				->icon('heroicon-o-arrow-down-tray')
				->iconColor('success')
				->actions([
					Action::make('download')
						->label('Download')
						->url($fileUrl)
						->openUrlInNewTab()
						->markAsRead()
						->button()
				])
				->sendToDatabase($user);
		}

		File::create([
			'user_id' => $user->id,
			'file_name' => $filename,
			'file_path' => $filepath,
			'download_url' => $fileUrl,
			'scheduled_deletion_time' => $expiration,
		]);

		$send_to_email = $this->data['send_to_email'] ?? false;

		if ($send_to_email) {
			$this->sendEmail($fullPath, $user);
		}

		Log::info('4203 --> PaymentGoalReportExcel: Finished.');
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

	protected function sendEmail(string $fullPath, $user): void
	{
		$email = getSetting('custom_payment_email');
		$authorName = getSetting('author_name');

		$data = [
			'log_name' => 'payment_goal_notification',
			'email' => $email,
			'author_name' => $authorName,
			'subject' => 'Notifikasi: Laporan Target Pembayaran Excel',
			'created_at' => now()->toDateTimeString(),
			'attachments' => [$fullPath],
		];

		$mail = new \App\Mail\PaymentGoalResource\PaymentGoalReportMail($data);
		\Illuminate\Support\Facades\Mail::to($email)->queue($mail);
		$html = $mail->render();

		saveActivityLog([
			'log_name' => 'Notification',
			'description' => 'Payment Goal Excel Report by ' . $user->name,
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
