<?php

namespace App\Mail\BlogSubscriberResource;

use Illuminate\Support\Carbon;;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ResubscribeSubscriberMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  public function __construct(public array $data = [])
  {
    $url = getSetting('nova_blog_url');
    $token = $this->data['token'];

    $this->data = array_merge($this->data, [
      'subject' => 'Selamat Datang Kembali! (' . carbonTranslatedFormat(Carbon::now(), 'd M Y, H:i', 'id') . ')',
      'unsubscribe_url' => "{$url}/unsubscribe/{$token}",
      'blog_url' => $url,
      'resubscribed_at_formatted' => carbonTranslatedFormat(Carbon::now(), 'l, d F Y \p\u\k\u\l H.i', 'id'),
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
      view: 'blog-subscriber-resource.mails.resubscribe-subscriber-mail',
    );
  }

  public function attachments(): array
  {
    return [];
  }
}
