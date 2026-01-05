<?php

namespace App\Jobs\FileResource;

use App\Enums\EmailStatus;
use App\Models\Email;
use App\Models\File;
use App\Services\EmailResource\EmailService;
use Carbon\Carbon;
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
    Log::info('3556 --> RemoveFileJob: Started.');

    $now = Carbon::now()->toDateTimeString();
    $count = 0;

    File::where('scheduled_deletion_time', '<=', $now)->chunk(10, function (Collection $records) use (&$count) {
      foreach ($records as $record) {
        Log::info("3556 --> RemoveFileJob: Deleting file ID {$record->id}");
        $count++;
        $record->removeFile();
      }
    });

    $default = [
      'name'    => getSetting('author_name'),
      'email'   => getSetting('remove_file_email'),
      'subject' => 'Notifikasi: Pembersihan File Terjadwal Berhasil (' . carbonTranslatedFormat($now, 'd M Y, H.i') . ')',
      'message' => '<p>Kami menginformasikan bahwa sesuai dengan kebijakan retensi data, sistem telah melakukan penghapusan terhadap <strong>' . $count . ' file</strong> yang telah melewati batas waktu penyimpanan.</p><p>Pembersihan ini dilakukan untuk memastikan kepatuhan terhadap kebijakan privasi dan efisiensi infrastruktur kami.</p>',
      'status'  => EmailStatus::Draft,
    ];

    if ($count < 1) {
      $default['message'] = '<p>Kami menginformasikan bahwa sistem baru saja selesai melakukan pemeriksaan rutin sesuai kebijakan retensi data.</p><p>Hasil pemeriksaan menunjukkan <strong>tidak ada file</strong> yang perlu dihapus saat ini. Seluruh data yang tersimpan masih berada dalam batas waktu penyimpanan yang diizinkan.</p><p>Sistem tetap berjalan optimal dan pembersihan berikutnya akan dilakukan sesuai jadwal.</p>';
    }

    $email = Email::create($default);

    (new EmailService())->sendOrPreview($email);

    Log::info("3557 --> RemoveFileJob: Finished. {$count} files deleted.");
  }
}
