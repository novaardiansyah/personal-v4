<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;

class EmailTemplateController extends Controller
{
  public function preview(EmailTemplate $emailTemplate)
  {
    $message      = $emailTemplate->message;
    $placeholders = $emailTemplate->placeholders;

    foreach ($placeholders as $key => $value) {
      $message = str_replace('{' . $key . '}', $value, $message);
    }

    $data = [
      'message' => $message,
      'subject' => $emailTemplate->subject,
      'name'    => getSetting('author_name'),
    ];

    $html = view('email-resource.mails.default-mail', compact('data'))->render();
    $html = '<div style="margin-top: 30px; margin-bottom: 30px;">' . $html . '</div>';

    return response($html)->header('Content-Type', 'text/html');
  }
}
