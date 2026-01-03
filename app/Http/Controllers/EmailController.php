<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Services\EmailResource\EmailService;

class EmailController extends Controller
{
  public function preview(Email $email)
  {
    return (new EmailService())->sendOrPreview($email, true);
  }
}
