<?php

namespace App\Services;

use App\Enums\EmailStatus;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\ActivityLog;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailResource\EmailService;
use Illuminate\Support\Facades\Log;

class AuthService
{
	public function sendLoginTelegramNotification(User $user, array $context): void
	{
		$enableTelegram = textLower(getSetting('enable_login_telegram_notification', 'Yes')) === 'yes' ? true : false;

		if (!$enableTelegram) return;

		$interval = getSetting('interval_login_telegram_notification', '60 Minutes');
		$interval = (int) preg_replace('/\D/', '', $interval);

		$existingLog = ActivityLog::where('ip_address', $context['ip_address'])
			->where('event', 'Telegram Login Notification')
			->where('log_name', 'Notification')
			->where('causer_id', $user->id)
			->where('causer_type', User::class)
			->where('created_at', '>=', now()->subMinutes($interval))
			->first();

		if ($existingLog) return;

		$tgMessage = view('notifications.telegram.login-notification', [
			'user_email'  => $user->email,
			'ip_address'  => $context['ip_address'],
			'address'     => $context['address'],
			'geolocation' => $context['geolocation'],
			'timezone'    => $context['timezone'],
			'user_agent'  => $context['user_agent'],
			'login_date'  => $context['now_formatted'],
			'referer'     => $context['referer'],
		])->render();

		SendTelegramNotificationJob::dispatch($tgMessage);

		saveActivityLog([
			'log_name'     => 'Notification',
			'event'        => 'Telegram Login Notification',
			'description'  => 'Telegram login notification sent to ' . $user->email,
			'subject_id'   => $user->id,
			'subject_type' => User::class,
			'ip_address'   => $context['ip_address'],
			'causer_id'    => $user->id,
			'causer_type'  => User::class,
		]);
	}

	public function sendLoginEmailNotification(User $user, array $context): void
	{
		$enableEmail = textLower(getSetting('enable_login_email_notification', 'Yes')) === 'yes' ? true : false;

		if (!$enableEmail) return;

		$interval = getSetting('interval_login_email_notification', '60 Minutes');
		$interval = (int) preg_replace('/\D/', '', $interval);

		$existingLog = ActivityLog::where('ip_address', $context['ip_address'])
			->where('event', 'Mail Login Notification')
			->where('log_name', 'Notification')
			->where('causer_id', $user->id)
			->where('causer_type', User::class)
			->where('created_at', '>=', now()->subMinutes($interval))
			->first();

		if ($existingLog) return;

		$template = null;

		if ($context['guard'] === 'api') {
			$template = EmailTemplate::where('alias', 'login_email_notification_api')->first();
		} else {
			$template = EmailTemplate::where('alias', 'login_email_notification_web')->first();
		}

		if (!$template) {
			Log::info('3468 --> No template found for login email notification', $user->toArray());
			return;
		}

		$author_name   = getSetting('author_name');
		$author_email  = getSetting('login_email_notification');

		$placeholders = array_merge($template->placeholders, [
			'user_email'  => $user->email,
			'ip_address'  => $context['ip_address'],
			'address'     => $context['address'],
			'geolocation' => $context['geolocation'],
			'timezone'    => $context['timezone'],
			'user_agent'  => $context['user_agent'],
			'login_date'  => $context['now_formatted'],
			'referer'     => $context['referer'],
		]);

		$message = $template->message;

		foreach ($placeholders as $key => $value) {
			$message = str_replace('{' . $key . '}', $value, $message);
		}

		$default = [
			'name'       => $author_name,
			'email'      => $author_email,
			'subject'    => $template->subject . ' (' . $context['now_formatted'] . ')',
			'message'    => $message,
			'status'     => EmailStatus::Draft,
			'has_header' => true,
			'has_footer' => true,
		];

		$email = Email::create($default);

		(new EmailService())->sendOrPreview($email, false, $context['device_info']);

		saveActivityLog([
			'log_name'     => 'Notification',
			'event'        => 'Mail Login Notification',
			'description'  => 'Mail login notification sent to ' . $user->email,
			'subject_id'   => $user->id,
			'subject_type' => User::class,
			'ip_address'   => $context['ip_address'],
			'causer_id'    => $user->id,
			'causer_type'  => User::class,
		]);
	}
}
