<?php

namespace App\Listeners;

use App\Mail\UserResource\NotifUserLoginMail;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    $user_id    = $event->user->id;
    $user_email = $event->user->email;
    $referer    = url('admin/login');
    $ip_address = request()->ip();
    $user_agent = request()->userAgent();

    // ! Check in ActivityLog by IP, if it already exists, no need to send the email again
    $interval = getSetting('interval_login_notification', '1 Hours');
    $interval = (int) preg_replace('/\D/', '', $interval);

    $existingLog = ActivityLog::where('ip_address', $ip_address)
      ->where('event', 'Login')
      ->where('log_name', 'Notification')
      ->where('subject_id', $user_id)
      ->where('created_at', '>=', now()->subHours($interval))
      ->first();

    $silentLog = [
      'user_id'    => $user_id,
      'user_email' => $user_email,
      'ip_address' => $ip_address,
      'user_agent' => $user_agent,
    ];

    $ipInfo = getIpInfo($ip_address);

    $country     = $ipInfo['country'];
    $city        = $ipInfo['city'];
    $region      = $ipInfo['region'];
    $postal      = $ipInfo['postal'];
    $geolocation = $ipInfo['geolocation'];
    $timezone    = $ipInfo['timezone'];
    $address     = $ipInfo['address'];
    
    saveActivityLog([
      'log_name'     => 'Notification',
      'description'  => 'Email Login Notification Sent',
      'event'        => 'Login',
      'subject_id'   => $user_id,
      'subject_type' => User::class,
      'ip_address'   => $ip_address,
      'country'      => $country,
      'city'         => $city,
      'region'       => $region,
      'postal'       => $postal,
      'geolocation'  => $geolocation,
      'timezone'     => $timezone,
      'user_agent'   => $user_agent,
      'referer'      => $referer,
      'properties'  => [
        'id'    => $user_id,
        'email' => $user_email,
      ],
    ]);

    // ! If it already exists and is still within the delay period, then no email notification needs to be sent again
    if ($existingLog) return;

    $emailData = [
      'email'       => getSetting('login_email_notification'),
      'author_name' => getSetting('author_name'),
      'log_name'    => 'notif_user_login',
      'subject'     => 'Notifikasi: Login pengguna dari situs web',
      'email_user'  => $event->user->email,
      'ip_address'  => $ip_address,
      'geolocation' => $geolocation,
      'user_agent'  => $user_agent,
      'timezone'    => $timezone,
      'referer'     => $referer,
      'address'     => $address,
      'created_at'  => $now,
    ];

    $enableEmailLogin = textLower(getSetting('enable_login_email_notification', 'Yes')) === 'yes' ? true : false;

    if ($enableEmailLogin) {
      \Log::info('3468 --> Sent email login notification', $silentLog);
      Mail::to($emailData['email'])->queue(new NotifUserLoginMail($emailData));
    }
  }
}
