<?php

namespace App\Jobs;

use App\Enums\EmailStatus;
use App\Models\ActivityLog;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailResource\EmailService;
use Illuminate\Support\Carbon;;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Sanctum\PersonalAccessToken;

class CleanExpiredTokens implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct()
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $now = Carbon::now()->toDateTimeString();
    $causer = getUser();

    $defaultLog = [
      'log_name' => 'Console',
      'event' => 'Scheduled',
      'description' => 'CleanExpiredTokens() Executed by ' . $causer->name,
      'causer_type' => User::class,
      'causer_id' => $causer->id,
      'properties' => [
        'now' => $now,
      ],
    ];

    $startLog = saveActivityLog($defaultLog);
    $deletedCount = PersonalAccessToken::where('expires_at', '<', $now)->delete();

    $template = null;

    if ((int) $deletedCount > 0) {
      $template = EmailTemplate::where('alias', 'clean_scheduled_token')->first();
    } else {
      $template = EmailTemplate::where('alias', 'check_scheduled_token')->first();
    }

    if ($template) {
      $author_email = getSetting('remove_file_email');
      $author_name  = getSetting('author_name');

      $placeholders = array_merge($template->placeholders, [
        'count' => $deletedCount,
      ]);

      $message = $template->message;

      foreach ($placeholders as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
      }

      $default = [
        'name'    => $author_name,
        'email'   => $author_email,
        'subject' => $template->subject . ' (' . carbonTranslatedFormat(Carbon::now(), 'd M Y, H.i', 'id') . ')',
        'message' => $message,
        'status'  => EmailStatus::Draft,
      ];

      $email = Email::create($default);

      (new EmailService())->sendOrPreview($email);
    }

    $defaultLog = array_merge($defaultLog, [
      'description' => 'CleanExpiredTokens() Successfully Executed by ' . $causer->name,
      'subject_type' => ActivityLog::class,
      'subject_id' => $startLog->id,
      'properties' => array_merge($defaultLog['properties'], [
        'deleted_count' => $deletedCount,
      ]),
    ]);

    saveActivityLog($defaultLog);
  }
}
