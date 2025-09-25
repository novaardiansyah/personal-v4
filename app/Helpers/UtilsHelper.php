<?php

use App\Models\ActivityLog;
use App\Models\File;
use App\Models\Generate;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

function getSetting(string $key, $default = null)
{
  return cache()->rememberForever("setting.{$key}", function () use ($key, $default) {
    return Setting::where('key', $key)->first()?->value ?? $default;
  });
}

function carbonTranslatedFormat(string $date, string $format = 'd/m/Y H:i'): string
{
  return Carbon::parse($date)->translatedFormat($format);
}

function toIndonesianCurrency(float $number = 0, int $precision = 0, string $currency = 'Rp', bool $showCurrency = true)
{
  $result = 0;

  if ($number < 0) {
    $result = '-' . $currency . number_format(abs($number), $precision, ',', '.');
  } else {
    $result = $currency . number_format($number, $precision, ',', '.');
  }

  if ($showCurrency)
    return $result;

  $replace = str_replace(range(0, 9), '-', $result);
  return $replace;
}

function makePdf(\Mpdf\Mpdf $mpdf, string $name, ?Model $user = null, $preview = false, $notification = true, $auto_close_tbody = true): array
{
  $user ??= getUser();

  $extension                = 'pdf';
  $directory                = 'filament-pdf';
  $filenameWithoutExtension = Uuid::uuid4() . "-{$name}";
  $filename                 = "{$filenameWithoutExtension}.{$extension}";
  $filepath                 = "{$directory}/{$filename}";

  $end_tbody = $auto_close_tbody ? '</tbody><tfoot><tr></tr></tfoot>' : '';

  $mpdf->WriteHTML($end_tbody . '
        </table>
      </body>
    </html>
  ');

  $mpdf->SetHTMLFooter(view('layout.footer')->render());

  if ($preview) {
    $mpdf->Output('', 'I'); // ! Output to browser for preview
    return [
      'filename'   => $filename,
      'filepath'   => $filepath,
      'signed_url' => null, // ! No signed URL for preview
    ];
  }

  $mpdf->Output(storage_path("app/{$filepath}"), 'F');

  $expiration = now()->addHours(24);

  $fileUrl = URL::temporarySignedRoute(
    'download',
    $expiration,
    ['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => $directory]
  );

  if ($notification) {
    Notification::make()
      ->title('PDF file ready')
      ->body('Your file is ready to download')
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
    'user_id'                 => $user->id,
    'file_name'               => $filename,
    'file_path'               => $filepath,
    'download_url'            => $fileUrl,
    'scheduled_deletion_time' => $expiration,
  ]);

  $properties = [
    'filename'   => $filename,
    'filepath'   => $filepath,
    'signed_url' => $fileUrl,
  ];

  return $properties;
}

function getCode(string $alias, bool $isNotPreview = true)
{
  $genn = Generate::withTrashed()->where('alias', $alias)->first();
  
  if (!$genn)
    return 'ER-' . random_int(10000, 99999);

  $date = now()->translatedFormat('ymd');
  $separator = Carbon::createFromFormat('ymd', $genn->separator)->translatedFormat('ymd');

  if ($genn->queue == 9999 || (substr($date, 0, 4) != substr($separator, 0, 4))) {
    $genn->queue = 1;
    $genn->separator = $date;
  }

  $queue = substr($date, 0, 4) . str_pad($genn->queue, 4, '0', STR_PAD_LEFT) . substr($date, 4, 2);

  if ($genn->prefix)
    $queue = $genn->prefix . $queue;
  if ($genn->suffix)
    $queue .= $genn->suffix;

  if ($isNotPreview) {
    $genn->queue += 1;
    $genn->save();
  }

  return $queue;
}

function getOptionMonths($short = false): array
{
  if ($short) {
    return [
      '01' => 'Jan',
      '02' => 'Feb',
      '03' => 'Mar',
      '04' => 'Apr',
      '05' => 'Mei',
      '06' => 'Jun',
      '07' => 'Jul',
      '08' => 'Agu',
      '09' => 'Sep',
      '10' => 'Okt',
      '11' => 'Nov',
      '12' => 'Des',
    ];
  }

  return [
    '1' => 'Januari',
    '2' => 'Februari',
    '3' => 'Maret',
    '4' => 'April',
    '5' => 'Mei',
    '6' => 'Juni',
    '7' => 'Juli',
    '8' => 'Agustus',
    '9' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember',
  ];
}

function textCapitalize($text)
{
  return trim(ucwords(strtolower($text)));
}

function textUpper($text)
{
  return trim(strtoupper($text));
}

function textLower($text)
{
  return trim(strtolower($text));
}

// function sendTelegramNotification($message = null, $location = null, $chat_id = null)
// {
//   $chat_id = $chat_id ?? config('services.telegram-bot-api.chat_id');

//   if ($message) {
//     NotificationFacade::route('telegram', $chat_id)->notify(new TelegramNotification([
//       'message' => $message
//     ]));

//     \Log::info('673 --> Telegram message notification sent');
//   }

//   if ($location) {
//     NotificationFacade::route('telegram', $chat_id)->notify(new TelegramLocationNotification($location));
//     \Log::info('674 --> Telegram location notification sent');
//   }
// }

function saveActivityLog(array $data = [], $modelMorp = null)
{
  $causer = getUser();
  
  $model    = $data['model'] ?? '';
  $event    = $data['event'] ?? '';
  $changes  = [];
  $oldValue = [];

  if ($modelMorp) {
    $changes = collect($modelMorp->getDirty())
    ->except($modelMorp->getHidden());

    if ($event == 'Updated') {
      $oldValue = $changes->mapWithKeys(fn ($value, $key) => [$key => $modelMorp->getOriginal($key)])->toArray();
    }

    $changes  = $changes->toArray();
  }

  ActivityLog::create(array_merge([
    'log_name'        => 'Resource',
    'description'     => "{$model} {$event} by {$causer->name}",
    'event'           => $event,
    'causer_type'     => User::class,
    'causer_id'       => $causer->id,
    'prev_properties' => $oldValue,
    'properties'      => $changes,
  ], $data));
}

function getUser(?int $userId = null): Collection | User | null
{
  if ($userId) {
    return User::find($userId);
  }

  $user_code = getSetting('default_system_user');
  return auth()->user() ?? User::where('code', $user_code)->first();
}

function getIpInfo(?string $ipAddress = null): array
{
  $ipAddress = $ipAddress ?? request()->ip();
  $ipAddress = explode(',', $ipAddress)[0] ?? '127.0.0.2';

  $url = getSetting('ipinfo_api_url');

  $replace = [
    'ip_address' => $ipAddress,
    'token'      => config('services.ipinfo.token')
  ];

  foreach ($replace as $key => $value) {
    $url = str_replace('{' . $key . '}', $value, $url);
  }

  $ipInfo = Http::get($url)->json();

  $country     = $ipInfo['country'] ?? null;
  $city        = $ipInfo['city'] ?? null;
  $region      = $ipInfo['region'] ?? null;
  $postal      = $ipInfo['postal'] ?? null;
  $geolocation = $ipInfo['loc'] ?? null;
  $geolocation = $geolocation ? str_replace(',', ', ', $geolocation) : null;
  $timezone    = $ipInfo['timezone'] ?? null;

  $address = null;
  if ($city) {
    $address = trim("{$city}, {$region}, {$country} ({$postal})");
  }

  return [
    'ip_address'  => $ipAddress,
    'country'     => $country,
    'city'        => $city,
    'region'      => $region,
    'postal'      => $postal,
    'geolocation' => $geolocation,
    'timezone'    => $timezone,
    'address'     => $address,
    'raw_data'    => $ipInfo
  ];
}
