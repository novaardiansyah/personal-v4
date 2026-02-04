<?php

namespace App\Jobs\FileResource;

use App\Enums\EmailStatus;
use App\Models\ActivityLog;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\File;
use App\Models\User;
use App\Services\EmailResource\EmailService;
use Illuminate\Support\Carbon;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RemoveFileJob implements ShouldQueue
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
    $now    = Carbon::now()->toDateTimeString();
    $causer = getUser();
    $count  = 0;

    $defaultLog = [
      'log_name'    => 'Console',
      'event'       => 'Scheduled',
      'description' => 'RemoveFileJob() Executed by ' . $causer->name,
      'causer_type' => User::class,
      'causer_id'   => $causer->id,
      'properties'  => [
        'now'   => $now,
        'count' => $count,
      ],
    ];

    $startLog = saveActivityLog($defaultLog);

    File::where('scheduled_deletion_time', '<=', $now)->chunk(10, function (Collection $records) use (&$count) {
      foreach ($records as $record) {
        Log::info("3556 --> RemoveFileJob: Deleting file ID {$record->id}");
        $count++;
        $record->removeFile();
      }
    });

    $template = null;

    if ($count > 0) {
      $template = EmailTemplate::where('alias', 'clean_scheduled_files')->first();
    } else {
      $template = EmailTemplate::where('alias', 'check_scheduled_files')->first();
    }

    if ($template) {
      $author_email = getSetting('remove_file_email');
      $author_name  = getSetting('author_name');

      $placeholders = array_merge($template->placeholders, [
        'count' => $count,
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
      'description'  => 'RemoveFileJob() Successfully Executed by ' . $causer->name,
      'subject_type' => ActivityLog::class,
      'subject_id'   => $startLog->id,
      'properties'   => array_merge($defaultLog['properties'], [
        'count'        => $count,
        'author_name'  => $author_name,
        'author_email' => $author_email,
      ]),
    ]);

    saveActivityLog($defaultLog);
  }
}
