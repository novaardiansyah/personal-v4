<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ContactMessageResource\StoreMessageJob;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator AS validationValidator;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class ContactMessageController extends Controller
{
  public function store(Request $request)
  {
    $validator = $this->_set_validator($request);

    if ($validator->fails()) {
      return response()->json([
        'message' => 'Invalid Form!',
        'errors'  => $validator->errors()
      ], 422);
    }

    $validated = $validator->validate();

    $check_captcha = Http::asForm()->post(config('services.cloudflare.turnstile.site_url'), [
      'secret'   => config('services.cloudflare.turnstile.secret_key'),
      'response' => $validated['captcha_token'],
    ])->json();
    
    if (!$check_captcha['success']) {
      return response()->json([
        'message' => 'You have entered an invalid captcha, please try again!',
      ], 422);
    }

    $contactMessage = ContactMessage::where('email', $validated['email'])
      ->where('created_at', '>=', now()->subHours(6))->first();

    if ($contactMessage) {
      return response()->json(['message' => 'You have already sent a message recently. We will reply as soon as possible, thank you!'], 422);
    }

    $save = array_merge($validated, [
      'path'       => $request->path(),
      'url'        => $request->url(),
      'full_url'   => $request->fullUrl(),
      'ip_address' => $request->ip(),
      'user_agent' => $request->userAgent(),
    ]);
    
    StoreMessageJob::dispatch($save);
    
    return response()->json(['message' => 'Thank you for your message, it has been sent. We will reply as soon as possible, thank you!'], 200);
  }

  private function _set_validator(Request $request): validationValidator
  {
    $rules = [
      'name'          => 'required|min:3',
      'email'         => 'required|email',
      'subject'       => 'required|min:5',
      'message'       => 'required|min:5|max:2000',
      'captcha_token' => 'required|string',
      'ip_address'    => 'nullable|string',
      'user_agent'    => 'nullable|string',
    ];

    $messages = [
      'name.required'          => 'Please enter your name',
      'name.min'               => 'Name must be at least 3 characters',
      'email.required'         => 'Please enter your email',
      'email.email'            => 'Email is invalid',
      'subject.required'       => 'Please enter your subject',
      'subject.min'            => 'Subject must be at least 5 characters',
      'message.required'       => 'Please enter your message',
      'message.min'            => 'Message must be at least 5 characters',
      'message.max'            => 'Message must be at most 400 characters',
      'captcha_token.required' => 'Please complete the captcha',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);
    return $validator;
  }
}