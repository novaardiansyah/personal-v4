<?php

namespace App\Jobs\ContactMessageResource;

use App\Mail\ContactMessageResource\NotifContactMail;
use App\Mail\ContactMessageResource\ReplyContactMail;
use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class StoreMessageJob implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct(public array $data)
  {
    // You can initialize any properties or perform any setup here
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $now    = now()->toDateTimeString();
    $causer = getUser();
    $ipInfo = getIpInfo($this->data['ip_address'] ?? null);

    $geolocation = $ipInfo['geolocation'];
    $timezone    = $ipInfo['timezone'];
    $address     = $ipInfo['address'];
    $ip_address  = $ipInfo['ip_address'];

    $data = array_merge($this->data, [
      'ip_address' => $ip_address,
    ]);

    $contactMessage = ContactMessage::create($data);

    $notif_reply = [
      'log_name'   => 'reply_contact_message',
      'email'      => $data['email'],
      'subject'    => 'Terima Kasih Telah Menghubungi Saya',
      'name'       => $data['name'],
      'created_at' => $now,
    ];

    Mail::to($contactMessage->email)->queue(new ReplyContactMail($notif_reply));
    $htmlReply = (new ReplyContactMail($notif_reply))->render();

    saveActivityLog([
      'log_name'    => 'Notification',
      'description' => 'Reply Contact Mail by ' . $causer->name,
      'event'       => 'Mail Notification',
      'properties'  => [
        'email'   => $notif_reply['email'],
        'subject' => $notif_reply['subject'],
        'html'    => $htmlReply,
      ],
    ]);

    $notif_params = array_merge($data, [
      'subject'         => 'Notifikasi: Pesan masuk baru dari situs web',
      'author_name'     => getSetting('author_name'),
      'email'           => getSetting('reply_email_to'),
      'log_name'        => 'notif_contact_message',
      'email_contact'   => $data['email'],
      'subject_contact' => $data['subject'],
      'address'         => $address,
      'timezone'        => $timezone,
      'geolocation'     => $geolocation,
      'created_at'      => $now,
    ]);

    Mail::to($notif_params['email'])->queue(new NotifContactMail($notif_params));
    $htmlNotif = (new NotifContactMail($notif_params))->render();

    saveActivityLog([
      'log_name'    => 'Notification',
      'description' => 'Notif Contact Mail by ' . $causer->name,
      'event'       => 'Mail Notification',
      'properties'  => [
        'email'   => $notif_params['email'],
        'subject' => $notif_params['subject'],
        'html'    => $htmlNotif,
      ],
    ]);
  }
}
