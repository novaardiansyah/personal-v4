<?php

namespace App\Mail\BlogSubscriberResource;

use Illuminate\Support\Carbon;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class VerifySubscriberMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public function __construct(public array $data = [])
  {
    $url = getSetting('nova_blog_url');
    $token = $this->data['token'];

    $this->data = array_merge($this->data, [
      'subject'    => 'Verifikasi Langganan Nova Blog (' . carbonTranslatedFormat(Carbon::now(), 'd M Y, H:i', 'id') . ')',
      'verify_url' => "{$url}/verify-subscriber/{$token}",
    ]);
  }

  public function envelope(): Envelope
  {
    return new Envelope(
      subject: $this->data['subject'],
      replyTo: [
        new Address(getSetting('reply_email_to'), getSetting('author_name')),
      ]
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'blog-subscriber-resource.mails.verify-subscriber-mail',
    );
  }

  public function attachments(): array
  {
    return [];
  }
}
