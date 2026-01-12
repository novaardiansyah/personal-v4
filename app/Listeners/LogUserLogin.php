<?php

namespace App\Listeners;

use App\Enums\EmailStatus;
use App\Models\ActivityLog;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailResource\EmailService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class LogUserLogin
{
  /**
   * Create the event listener.
   */
  public function __construct()
  {
    //
  }

  /**
   * Handle the event.
   */
  public function handle(Login $event): void
  {
    $now = Carbon::now();

    static $alreadyLogged = false;

    if ($alreadyLogged) {
      return;
    }

    $alreadyLogged = true;

    $user_id    = $event->user->id ?? '-1';
    $user       = getUser($user_id);

    if (!$user) return;
    
    $referer    = $event->guard === 'api' ? url('/api/auth/login') : url('admin/login');
    $ip_address = request()->ip();
    $user_agent = request()->userAgent();

    $ipInfo      = getIpInfo($ip_address);
    $country     = $ipInfo['country'];
    $city        = $ipInfo['city'];
    $region      = $ipInfo['region'];
    $postal      = $ipInfo['postal'];
    $geolocation = $ipInfo['geolocation'];
    $timezone    = $ipInfo['timezone'];
    $address     = $ipInfo['address'];

    $device_info  = [
      'ip_address'   => $ip_address,
      'country'      => $country,
      'city'         => $city,
      'region'       => $region,
      'postal'       => $postal,
      'geolocation'  => $geolocation,
      'timezone'     => $timezone,
      'user_agent'   => $user_agent,
      'referer'      => $referer,
    ];

    saveActivityLog(array_merge([
      'log_name'     => 'Resource',
      'description'  => 'User ' . $user->name . ' successfully logged in',
      'event'        => 'Login',
      'subject_id'   => $user->id,
      'subject_type' => User::class,
      'properties' => [
        'id'    => $user->id,
        'name'  => $user->name,
        'email' => $user->email,
      ],
    ], $device_info));

    // ! Check in ActivityLog by IP, if it already exists, no need to send the email again
    $interval = getSetting('interval_login_notification', '1 Hours');
    $interval = (int) preg_replace('/\D/', '', $interval);

    $existingLog = ActivityLog::where('ip_address', $ip_address)
      ->where('event', 'Mail Notification')
      ->where('log_name', 'Notification')
      ->where('causer_id', $user->id)
      ->where('causer_type', User::class)
      ->where('created_at', '>=', now()->subHours($interval))
      ->first();

    // ! If it already exists and is still within the delay period, then no email notification needs to be sent again
    if ($existingLog) return;

    $enableEmailLogin = textLower(getSetting('enable_login_email_notification', 'Yes')) === 'yes' ? true : false;

    if ($enableEmailLogin) {
      $template = null;

      if ($event->guard === 'api') {
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
      $now_formatted = carbonTranslatedFormat($now, 'd M Y, H.i', 'id');

      $placeholders = array_merge($template->placeholders, [
        'user_email'  => $user->email,
        'ip_address'  => $ip_address,
        'address'     => $address,
        'geolocation' => $geolocation,
        'timezone'    => $timezone,
        'user_agent'  => $user_agent,
        'login_date'  => $now_formatted,
        'referer'     => $referer,
      ]);

      $message = $template->message;
  
      foreach ($placeholders as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
      }

      $default = [
        'name'    => $author_name,
        'email'   => $author_email,
        'subject' => $template->subject . ' (' . $now_formatted . ')',
        'message' => $message,
        'status'  => EmailStatus::Draft,
      ];
  
      $email = Email::create($default);
  
      (new EmailService())->sendOrPreview($email, false, $device_info);
    }
  }
}
