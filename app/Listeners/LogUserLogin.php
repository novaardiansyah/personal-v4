<?php

namespace App\Listeners;

use App\Jobs\SendLoginNotificationJob;
use App\Models\User;
use Illuminate\Support\Carbon;

use Illuminate\Auth\Events\Login;

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

		$now_formatted = carbonTranslatedFormat($now, 'd M Y, H.i', 'id');

		$context = [
			'address'       => $address,
			'now_formatted' => $now_formatted,
			'guard'         => $event->guard,
			'device_info'   => $device_info,
		];

		SendLoginNotificationJob::dispatch($user, $context);
	}
}
