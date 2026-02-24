<?php

namespace App\Mail\PaymentGoalResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class PaymentGoalReportMail extends Mailable implements ShouldQueue
{
	use Queueable, SerializesModels;

	public function __construct(
		public array $data = [],
	) {
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
			view: 'payment-goal-resource.mails.payment-goal-report-mail',
		);
	}

	public function attachments(): array
	{
		return $this->data['attachments'] ?? [];
	}
}
