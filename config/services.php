<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Third Party Services
  |--------------------------------------------------------------------------
  |
  | This file is for storing the credentials for third party services such
  | as Mailgun, Postmark, AWS and more. This file provides the de facto
  | location for this type of information, allowing packages to have
  | a conventional file to locate the various service credentials.
  |
  */

  'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
  ],

  'resend' => [
    'key' => env('RESEND_KEY'),
  ],

  'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
  ],

  'slack' => [
    'notifications' => [
      'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
      'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
    ],
  ],

  'ipinfo' => [
    'token' => env('IPINFO_TOKEN', ''),
  ],

  'cloudflare' => [
    'turnstile' => [
      'site_url'   => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
      'secret_key' => env('CF_CAPTCHA_SECRET', '-'),
    ],
    'r2' => [
      'account_id'        => env('R2_ACCOUNT_ID'),
      'access_key_id'     => env('R2_ACCESS_KEY_ID'),
      'secret_access_key' => env('R2_SECRET_ACCESS_KEY'),
      'bucket_name'       => env('R2_BUCKET_NAME'),
      'endpoint_url'      => env('R2_ENDPOINT_URL'),
      'enabled'           => env('R2_ENABLED', true),
    ],
  ],

  'telegram-bot-api' => [
    'token' => env('TELEGRAM_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
  ],

  'self' => [
    'short_url_token' => env('SHORTURL_TOKEN'),
    'cdn_api_url'     => env('CDN_API_URL'),
    'cdn_api_key'     => env('CDN_API_KEY'),
    'cdn_url'         => env('CDN_URL'),
    'webhook_secret'  => env('WEBHOOK_SECRET'),
  ],

  'ai_assistant' => [
    'api_url'     => env('CHATBOT_API_URL', 'https://9router.novaardiansyah.id/v1'),
    'api_key'     => env('CHATBOT_API_KEY'),
    'model'       => env('CHATBOT_MODEL', 'general-chat'),
    'max_tokens'  => (int) env('CHATBOT_MAX_TOKENS', 3072),
    'temperature' => (float) env('CHATBOT_TEMPERATURE', 0.3),
  ],
];
