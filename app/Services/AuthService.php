<?php

namespace App\Services;

use App\Enums\DiscordMessageStatus;
use App\Enums\EmailStatus;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\ActivityLog;
use App\Models\DiscordMessage;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailResource\EmailService;
use Illuminate\Support\Facades\Log;

class AuthService
{
	public function sendLoginTelegramNotification(User $user, array $context): void
	{
		$enableTelegram = textLower(getSetting('enable_login_telegram_notification', 'Yes', User::class)) === 'yes' ? true : false;
		$ip_address = $context['device_info']['ip_address'] ?? null;

		if (!$enableTelegram) return;

		$interval = getSetting('interval_login_telegram_notification', '60 Minutes', User::class);
		$interval = (int) preg_replace('/\D/', '', $interval);

		$existingLog = ActivityLog::where('ip_address', $ip_address)
			->where('event', 'Telegram Login Notification')
			->where('log_name', 'Notification')
			->where('causer_id', $user->id)
			->where('causer_type', User::class)
			->where('created_at', '>=', now()->subMinutes($interval))
			->first();

		if ($existingLog) return;

		$tgMessage = view('notifications.telegram.login-notification', [
			'user_email'  => $user->email,
			'ip_address'  => $ip_address ?? '-',
			'address'     => $context['address'],
			'geolocation' => $context['device_info']['geolocation'] ?? '-',
			'timezone'    => $context['device_info']['timezone'] ?? '-',
			'user_agent'  => $context['device_info']['user_agent'] ?? '-',
			'login_date'  => $context['now_formatted'],
			'referer'     => $context['device_info']['referer'] ?? '-',
		])->render();

		SendTelegramNotificationJob::dispatch($tgMessage);

		saveActivityLog(array_merge([
			'log_name'     => 'Notification',
			'event'        => 'Telegram Login Notification',
			'description'  => 'Telegram login notification sent to ' . $user->email,
			'subject_id'   => $user->id,
			'subject_type' => User::class,
			'causer_id'    => $user->id,
			'causer_type'  => User::class,
		], $context['device_info']));
	}

	public function sendLoginEmailNotification(User $user, array $context): void
	{
		$enableEmail = textLower(getSetting('enable_login_email_notification', 'Yes', User::class)) === 'yes' ? true : false;
		$ip_address = $context['device_info']['ip_address'] ?? null;

		if (!$enableEmail) return;

		$interval = getSetting('interval_login_email_notification', '60 Minutes', User::class);
		$interval = (int) preg_replace('/\D/', '', $interval);

		$existingLog = ActivityLog::where('ip_address', $ip_address)
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
		$author_email  = getSetting('login_email_notification', null, User::class);

		$placeholders = array_merge($template->placeholders ?? [], [
			'user_email'  => $user->email,
			'ip_address'  => $ip_address ?? '-',
			'address'     => $context['address'],
			'geolocation' => $context['device_info']['geolocation'] ?? '-',
			'timezone'    => $context['device_info']['timezone'] ?? '-',
			'user_agent'  => $context['device_info']['user_agent'] ?? '-',
			'login_date'  => $context['now_formatted'],
			'referer'     => $context['device_info']['referer'] ?? '-',
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

		saveActivityLog(array_merge([
			'log_name'     => 'Notification',
			'event'        => 'Mail Login Notification',
			'description'  => 'Mail login notification sent to ' . $user->email,
			'subject_id'   => $user->id,
			'subject_type' => User::class,
			'causer_id'    => $user->id,
			'causer_type'  => User::class,
		], $context['device_info']));
	}

	public function sendLoginDiscordNotification(User $user, array $context): void
	{
		$enableDiscord = textLower(getSetting('enable_login_discord_notification', 'Yes', User::class)) === 'yes' ? true : false;
		$ip_address    = $context['device_info']['ip_address'] ?? null;

		if (!$enableDiscord) return;

		$interval = getSetting('interval_login_discord_notification', '60 Minutes', User::class);
		$interval = (int) preg_replace('/\D/', '', $interval);

		$existingLog = ActivityLog::where('ip_address', $ip_address)
			->where('event', 'Discord Login Notification')
			->where('log_name', 'Notification')
			->where('causer_id', $user->id)
			->where('causer_type', User::class)
			->where('created_at', '>=', now()->subMinutes($interval))
			->first();

		if ($existingLog) return;

		$webhookSetting = getSetting('login_discord_webhook', null, User::class);
		if (empty($webhookSetting)) return;

		$service = app(DiscordWebhookService::class);
		$webhook = $service->resolveWebhook($webhookSetting);

		if (!$webhook) return;

		$payload = $service->buildLoginReportPayload($user, $context);

		DiscordMessage::create([
			'webhook_id' => $webhook->id,
			'content'    => $payload,
			'status'     => DiscordMessageStatus::Pending,
		]);

		saveActivityLog(array_merge([
			'log_name'     => 'Notification',
			'event'        => 'Discord Login Notification',
			'description'  => 'Discord login notification for ' . $user->email . ' will be sent by ' . $webhook->name . ' (' . $webhook->uid . ')',
			'subject_id'   => $user->id,
			'subject_type' => User::class,
			'causer_id'    => $user->id,
			'causer_type'  => User::class,
		], $context['device_info']));
	}
}
