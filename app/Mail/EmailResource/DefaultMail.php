<?php

namespace App\Mail\EmailResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;

class DefaultMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   */
  public function __construct(public array $data = [])
  {
    //
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: $this->data['subject'] ?? 'No Subject',
      replyTo: [
        new Address(getSetting('reply_email_to'), getSetting('author_name')),
      ]
    );
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    // * view('email-resource.mails.default-mail')
    return new Content(
      view: 'email-resource.mails.default-mail',
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    return [];
  }
}
