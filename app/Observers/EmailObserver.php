<?php

namespace App\Observers;

use App\Models\Email;
use App\Models\File;
use Illuminate\Support\Facades\URL;

class EmailObserver
{
  public function created(Email $email): void
  {
    $user = getUser();

    if (!empty($email->attachments)) {
      foreach ($email->attachments as $attachment) {
        $filename                 = pathinfo($attachment, PATHINFO_BASENAME);
        $filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $extension                = pathinfo($filename, PATHINFO_EXTENSION);
        
        $expiration = now()->addDay();
        $fileUrl = URL::temporarySignedRoute(
          'download',
          $expiration,
          ['path' => $filenameWithoutExtension, 'extension' => $extension, 'directory' => 'public/attachments']
        );

        File::create([
          'user_id'                 => $user->id,
          'file_name'               => $filename,
          'file_path'               => $attachment,
          'download_url'            => $fileUrl,
          'scheduled_deletion_time' => $expiration,
          'subject_type'            => Email::class,
          'subject_id'              => $email->id,
        ]);
      }
    }

    $this->_log('Created', $email);
  }

  public function updated(Email $email): void
  {
    $this->_log('Updated', $email);
  }

  public function deleted(Email $email): void
  {
    $this->_log('Deleted', $email);
  }

  public function restored(Email $email): void
  {
    $this->_log('Restored', $email);
  }

  public function forceDeleted(Email $email): void
  {
    $this->_log('Force Deleted', $email);
  }

  private function _log(string $event, Email $email): void
  {
    saveActivityLog([
      'event'        => $event,
      'model'        => 'Email',
      'subject_type' => Email::class,
      'subject_id'   => $email->id,
    ], $email);
  }
}
