<?php

namespace App\Http\Controllers;

use App\Models\Email;

class EmailController extends Controller
{
  public function preview(Email $email)
  {
    $data = [
      'name'    => $email->name ?? explode('@', $email->email)[0],
      'subject' => $email->subject,
      'message' => $email->message,
    ];

    $html = view('email-resource.mails.default-mail', compact('data'))->render();
    $html = '<div style="margin-top: 30px; margin-bottom: 30px;">' . $html . '</div>';
    return response($html)->header('Content-Type', 'text/html');
  }
}
