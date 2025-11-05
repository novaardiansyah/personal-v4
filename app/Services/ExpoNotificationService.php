<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

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
     * Send push notification to single or multiple devices
     *
     * @param array|string $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array|null $channels
     * @return array
     */
    public function sendNotification(
        array|string $tokens,
        string $title,
        string $body,
        array $data = [],
        ?array $channels = null
    ): array {
        if (is_string($tokens)) {
            $tokens = [$tokens];
        }

        $messages = [];

        foreach ($tokens as $token) {
            $message = [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ];

            if ($channels) {
                $message['channelId'] = $channels[0];
            }

            $messages[] = $message;
        }

        return $this->sendBatch($messages);
    }

    /**
     * Send notification to specific user by user ID
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $user = \App\Models\User::find($userId);

        if (!$user || !$user->notification_token || !$user->has_allow_notification) {
            return [
                'success' => false,
                'message' => 'User not found or notification not allowed',
                'data' => []
            ];
        }

        return $this->sendNotification($user->notification_token, $title, $body, $data);
    }

    /**
     * Send batch notifications
     *
     * @param array $messages
     * @return array
     */
    protected function sendBatch(array $messages): array
    {
        try {
            $response = $this->client->post($this->expoApiUrl, [
                'json' => $messages,
                'headers' => [
                    'Accept' => 'application/json',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            $results = [];
            foreach ($responseData['data'] as $index => $result) {
                $results[] = [
                    'token' => $messages[$index]['to'],
                    'status' => $result['status'] ?? 'unknown',
                    'message' => $result['message'] ?? '',
                    'details' => $result['details'] ?? null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Notifications sent successfully',
                'data' => $results
            ];

        } catch (RequestException $e) {
            Log::error('Expo notification error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to send notifications: ' . $e->getMessage(),
                'data' => []
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