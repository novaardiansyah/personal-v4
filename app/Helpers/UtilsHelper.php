<?php

use App\Models\ActivityLog;
use App\Models\File;
use App\Models\Generate;
use App\Models\PushNotification;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExpoNotificationService;
use App\Services\TelegramService;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Str;
use \Illuminate\Http\UploadedFile;
use Spatie\Image\Image;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use \Mpdf\Mpdf;
use App\Events\TelegramNotificationEvent;
use \GuzzleHttp\Psr7\Response;

function getSetting(string $key, $default = null)
{
  return cache()->rememberForever("setting.{$key}", function () use ($key, $default) {
    return Setting::where('key', $key)->first()?->value ?? $default;
  });
}

function carbonTranslatedFormat(string $date, string $format = 'd/m/Y H:i', ?string $locale = null): string
{
  if ($locale)
    Carbon::setLocale($locale);
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

function makePdf(Mpdf $mpdf, ?Model $user = null, bool $preview = false, bool $notification = true, bool $auto_close_tbody = true): array
{
  $user ??= getUser();

  $extension                = 'pdf';
  $directory                = 'public/attachments';
  $filenameWithoutExtension = Str::orderedUuid()->toString();
  $filename                 = "{$filenameWithoutExtension}.{$extension}";
  $filepath                 = "{$directory}/{$filename}";
  $fullpath                 = storage_path("app/{$filepath}");

  Storage::disk('public')->makeDirectory('attachments');

  $end_tbody = $auto_close_tbody ? '</tbody><tfoot><tr></tr></tfoot>' : '';

  $mpdf->WriteHTML($end_tbody . '
        </table>
        <div style="height: 30px;"></div>
      </body>
    </html>
  ');

  $mpdf->SetHTMLFooter(view('layout.footer')->render());

  if ($preview) {
    $mpdf->Output('', 'I'); // ! Output to browser for preview
    return [
      'filename' => $filename,
      'filepath' => $filepath,
      'signed_url' => null, // ! No signed URL for preview
    ];
  }

  $mpdf->Output($fullpath, 'F');

  $expiration = now()->addMonth();

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
    'user_id' => $user->id,
    'file_name' => $filename,
    'file_path' => $filepath,
    'download_url' => $fileUrl,
    'scheduled_deletion_time' => $expiration,
  ]);

  $properties = [
    'filename' => $filename,
    'filepath' => $filepath,
    'signed_url' => $fileUrl,
    'fullpath' => $fullpath,
  ];

  return $properties;
}

function getCode(string $alias, bool $isNotPreview = true)
{
  $genn = Generate::withTrashed()->where('alias', $alias)->first();
  $date = now()->translatedFormat('ymd');

  if (!$genn) {
    $queue = substr($date, 0, 4) . substr(time(), -4) . substr($date, 4, 2);
    return 'ER-' . $queue;
  }

  $separator = Carbon::createFromFormat('ymd', $genn->separator)->translatedFormat('ymd');

  $diffMonthAndYear = substr($date, 0, 4) != substr($separator, 0, 4);
  $maxLimitQueue = 9999;

  if ((int) $genn->queue >= $maxLimitQueue || $diffMonthAndYear) {
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

function saveActivityLog(array $data = [], $modelMorp = null): ActivityLog
{
  $causer = getUser();

  $model = $data['model'] ?? '';
  $event = $data['event'] ?? '';
  $changes = [];
  $oldValue = [];

  if ($modelMorp) {
    $changes = collect($modelMorp->getAttributes())
      ->except($modelMorp->getHidden());

    if ($event == 'Updated') {
      $changes = collect($modelMorp->getDirty())
        ->except($modelMorp->getHidden());

      $oldValue = $changes->mapWithKeys(fn($value, $key) => [$key => $modelMorp->getOriginal($key)])->toArray();
    }

    $changes = is_array($changes) ? $changes : $changes->toArray();
  }

  unset($data['model']);

  return ActivityLog::create(array_merge([
    'log_name' => 'Resource',
    'description' => "{$model} {$event} by {$causer->name}",
    'event' => $event,
    'causer_type' => User::class,
    'causer_id' => $causer->id,
    'prev_properties' => $oldValue,
    'properties' => $changes,
  ], $data));
}

function getUser(?int $userId = null): Collection|User|null
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
    'token' => config('services.ipinfo.token')
  ];

  foreach ($replace as $key => $value) {
    $url = str_replace('{' . $key . '}', $value, $url);
  }

  $ipInfo = Http::get($url)->json();

  $country = $ipInfo['country'] ?? null;
  $city = $ipInfo['city'] ?? null;
  $region = $ipInfo['region'] ?? null;
  $postal = $ipInfo['postal'] ?? null;
  $geolocation = $ipInfo['loc'] ?? null;
  $geolocation = $geolocation ? str_replace(',', ', ', $geolocation) : null;
  $timezone = $ipInfo['timezone'] ?? null;

  $address = null;
  if ($city) {
    $address = trim("{$city}, {$region}, {$country} ({$postal})");
  }

  return [
    'ip_address' => $ipAddress,
    'country' => $country,
    'city' => $city,
    'region' => $region,
    'postal' => $postal,
    'geolocation' => $geolocation,
    'timezone' => $timezone,
    'address' => $address,
    'raw_data' => $ipInfo
  ];
}

function copyFileWithRandomName(string $defaultPath): string
{
  $sourcePath = storage_path('app/public/' . $defaultPath);

  if (!file_exists($sourcePath)) {
    return $defaultPath;
  }

  $pathInfo = pathinfo($defaultPath);
  $extension = $pathInfo['extension'] ?? 'png';
  $directory = $pathInfo['dirname'];

  $randomName = Carbon::now()->format('YmdHis') . '_' . str()->random(12) . '.' . $extension;
  $newPath = $directory . '/' . $randomName;

  $targetPath = storage_path('app/public/' . $newPath);
  $targetDirectory = storage_path('app/public/' . $directory);

  if (!is_dir($targetDirectory)) {
    mkdir($targetDirectory, 0755, true);
  }

  if (copy($sourcePath, $targetPath)) {
    return $newPath;
  }

  return $defaultPath;
}

function sendPushNotification(User $user, PushNotification $record): array
{
  if (!$user->has_allow_notification) {
    return [
      'success' => false,
      'message' => 'User has disabled notifications'
    ];
  }

  if (!$user->notification_token) {
    return [
      'success' => false,
      'message' => 'No notification token found for this user'
    ];
  }

  $record->token = $user->notification_token;

  $notificationService = app(ExpoNotificationService::class);

  $result = $notificationService->sendNotification(
    $user->notification_token,
    $record->title,
    $record->body,
    $record->data
  );

  if ($result['success']) {
    $record->sent_at = Carbon::now();
    $record->response_data = $result['data'];
  } else {
    $record->error_message = $result['message'] . ': ' . $result['error'] ?? $result['message'];
  }

  if ($record->isDirty()) {
    $record->save();
  }

  return $result;
}

function sendTelegramNotification(string $message): void
{
  $user = (object) ['telegram_id' => config('services.telegram-bot-api.chat_id')];
  $telegramService = app(TelegramService::class);

  try {
    $response = $telegramService->toTelegram($user)->content($message)->send();
    $body = [];
    if ($response instanceof Response) {
      $body = json_decode($response->getBody()->getContents(), true) ?? [];
    }
    event(new TelegramNotificationEvent($message, 'Sent', $body));
  } catch (\Throwable $e) {
    event(new TelegramNotificationEvent($message, 'Failed', ['error' => $e->getMessage()]));
  }
}

function processBase64Image(?string $base64Data, string $storagePath): ?string
{
  if (empty($base64Data)) {
    return null;
  }

  if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
    $extension = strtolower($matches[1]);
    $base64Image = substr($base64Data, strpos($base64Data, ',') + 1);
    $imageData = base64_decode($base64Image);

    if ($imageData !== false) {
      $filename = Str::random(25) . '.' . $extension;
      $fullPath = $storagePath . '/' . $filename;

      Storage::disk('public')->put($fullPath, $imageData);

      return $fullPath;
    }
  }

  return null;
}

function uploadAndOptimize($file, string $disk = 'public', string $folder = 'images'): array
{
  if ($file instanceof UploadedFile) {
    $extension = $file->getClientOriginalExtension();
    $originalName = Str::uuid7() . '.' . $extension;
    $originalPath = $folder . '/' . $originalName;

    Storage::disk($disk)->put($originalPath, file_get_contents($file));
  } else {
    $originalPath = $file;
    $originalName = basename($file);
  }

  $diskPath = Storage::disk($disk)->path($originalPath);

  $img = Image::load($diskPath);
  $originalWidth = $img->getWidth();

  $versions = [
    'small' => ['width' => 300, 'quality' => 35],
    'medium' => ['width' => 900, 'quality' => 55],
    'large' => ['width' => 1600, 'quality' => 65],
  ];

  $paths = [];

  foreach ($versions as $prefix => $opt) {
    $targetWidth = min($opt['width'], $originalWidth);
    $filename = $prefix . '-' . $originalName;
    $savePath = $folder . '/' . $filename;
    $fullPath = Storage::disk($disk)->path($savePath);

    Image::load($diskPath)
      ->width($targetWidth)
      ->quality($opt['quality'])
      ->save($fullPath);

    ImageOptimizer::optimize($fullPath);

    $paths[$prefix] = $savePath;
  }

  $paths['original'] = $originalPath;

  return $paths;
}

function normalizeValidationErrors(array $errors): array
{
  $normalizedErrors = [];

  foreach ($errors as $key => $messages) {
    $newKey = str_starts_with($key, 'data.')
      ? substr($key, 5)
      : $key;

    $normalizedErrors[$newKey] = $messages;
  }

  return $normalizedErrors;
}

function sizeFormat(float $size): string
{
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $i = floor(log($size, 1024));
  return round($size / pow(1024, $i), 2) . ' ' . $units[$i];
}

function secondsToHumanReadable(?int $seconds): string
{
  if (!$seconds) {
    return '';
  }

  $days = (int) ($seconds / 86400);
  $hours = (int) (($seconds % 86400) / 3600);
  $minutes = (int) (($seconds % 3600) / 60);
  $secs = $seconds % 60;

  $parts = [];

  if ($days >= 1) {
    $parts[] = "{$days} " . ($days === 1 ? 'day' : 'days');
  }

  if ($hours >= 1 || $days >= 1) {
    $parts[] = "{$hours} " . ($hours === 1 ? 'hour' : 'hours');
  }

  if ($minutes >= 1 || $hours >= 1 || $days >= 1) {
    $parts[] = "{$minutes} " . ($minutes === 1 ? 'minute' : 'minutes');
  }

  if ($secs > 0) {
    $parts[] = "{$secs} " . ($secs === 1 ? 'second' : 'seconds');
  }

  return implode(', ', $parts);
}
