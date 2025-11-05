<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ExpoNotificationService
{
  protected Client $client;
  protected string $expoApiUrl;

  public function __construct()
  {
    $this->client = new Client();
    $this->expoApiUrl = 'https://exp.host/--/api/v2/push/send';
  }

  /**
   * Send push notification to single device
   *
   * @param string $token
   * @param string $title
   * @param string $body
   * @param array $data
   * @param string|null $channel
   * @return array
   */
  public function sendNotification(
    string $token,
    string $title,
    string $body,
    array $data = [],
    ?string $channel = null
  ): array {
    $message = [
      'to' => $token,
      'sound' => 'default',
      'title' => $title,
      'body' => $body,
      'data' => $data,
    ];

    if ($channel) {
      $message['channelId'] = $channel;
    }

    return $this->sendSingle($message);
  }

  /**
   * Send notification to specific user
   *
   * @param User $user
   * @param string $title
   * @param string $body
   * @param array $data
   * @return array
   */
  public function sendToUser(User $user, string $title, string $body, array $data = []): array
  {
    if (!$user->notification_token || !$user->has_allow_notification) {
      return [
        'success' => false,
        'message' => 'User notification not allowed',
        'data' => null
      ];
    }

    return $this->sendNotification($user->notification_token, $title, $body, $data);
  }

  /**
   * Send single notification
   *
   * @param array $message
   * @return array
   */
  protected function sendSingle(array $message): array
  {
    try {
      $response = $this->client->post($this->expoApiUrl, [
        'json' => $message,
        'headers' => [
          'Accept' => 'application/json',
          'Accept-Encoding' => 'gzip, deflate',
          'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
      ]);

      $responseData = json_decode($response->getBody()->getContents(), true);

      
      $result = $responseData['data'] ?? [];
      $status = $result['status'] ?? 'unknown';
      $messageText = $result['message'] ?? '';
      $details = $result['details'] ?? null;

      if ($status === 'error') {
        return [
          'success' => false,
          'message' => $details['error'] ?? 'UnknownError',
          'error' => $messageText,
          'data' => null
        ];
      }

      if ($status === 'unknown') {
        return [
          'success' => false,
          'message' => 'UnknownStatus',
          'error' => 'Expo API returned unknown status',
          'data' => null
        ];
      }

      return [
        'success' => true,
        'message' => 'Notification sent successfully',
        'data' => $result['id'] ?? null
      ];

    } catch (RequestException $e) {
      
      return [
        'success' => false,
        'message' => 'Failed to send notification: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'data' => null
      ];
    }
  }

  /**
   * Validate Expo push token format
   *
   * @param string $token
   * @return bool
   */
  public function validateToken(string $token): bool
  {
    // Expo push tokens start with "ExponentPushToken[" or are a 32-character hex string
    return preg_match('/^ExponentPushToken\[([a-zA-Z0-9\-_]+)\]$/', $token) ||
      preg_match('/^[a-f0-9]{32}$/', $token);
  }
}
