<?php

namespace App\Mail\PaymentResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class DailyReportMail extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   */
  public function __construct(
    public array $data = [],
  ) { 
    // $this->updateToSuccess();
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      subject: $this->data['subject'],
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
    // * view('payment-resource.mails.daily-report-mail')
    return new Content(
      view: 'payment-resource.mails.daily-report-mail',
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    return $this->data['attachments'] ?? [];
  }
}
