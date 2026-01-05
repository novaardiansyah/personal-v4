<?php

namespace App\Services\EmailResource;

use App\Enums\EmailStatus;
use App\Mail\EmailResource\DefaultMail;
use App\Models\Email;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;

class EmailService
{
  public function sendOrPreview(Email $email, $preview = false): Response | bool
  {
    $data = $email->toArray();

    if (!$data['name']) {
      $data['name'] = explode('@', $data['email'])[0];
    }

    $html = (new DefaultMail($data))->render();
    
    if ($preview) {
      $html = '<div style="margin-top: 30px; margin-bottom: 30px;">' . $html . '</div>';
      return response($html)->header('Content-Type', 'text/html');
    }

    Mail::to($data['email'])->queue(new DefaultMail($data));

    saveActivityLog([
      'log_name'     => 'Notification',
      'description'  => 'Email Sent to ' . $data['email'],
      'event'        => 'Mail Notification',
      'subject_id'   => $email->id,
      'subject_type' => Email::class,
      'properties' => [
        'email'       => $data['email'],
        'subject'     => $data['subject'],
        'attachments' => [],
        'html'        => $html,
      ],
    ], $email);

    $email->update([
      'status' => EmailStatus::Sent,
    ]);

    return true;
  }
}