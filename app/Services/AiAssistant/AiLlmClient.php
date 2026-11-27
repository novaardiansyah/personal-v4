<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiLlmClient
{
  public function chat(array $payload): array
  {
    $url = config('services.ai_assistant.api_url');
    $key = config('services.ai_assistant.api_key');

    if (empty($url) || empty($key)) {
      return [
        'ok'     => false,
        'status' => 503,
        'data'   => null,
        'error'  => 'AI Assistant API URL or API key is not configured.',
      ];
    }

    $endpoint = rtrim($url, '/') . '/chat/completions';

    try {
      $response = Http::withToken($key)
        ->timeout(45)
        ->post($endpoint, $payload);

      if (!$response->successful()) {
        Log::error('AI Assistant request failed', [
          'status' => $response->status(),
          'body'   => $response->body(),
          'model'  => $payload['model'] ?? null,
        ]);

        return [
          'ok'     => false,
          'status' => $response->status(),
          'data'   => null,
          'error'  => "HTTP {$response->status()}: " . mb_substr($response->body(), 0, 500),
        ];
      }

      $rawBody     = $response->body();
      $cleanedJson = $rawBody;

      if (preg_match('/\{[\s\S]*\}/', $rawBody, $matches)) {
        $cleanedJson = $matches[0];
      }

      $decoded = json_decode($cleanedJson, true);
      if (!is_array($decoded)) {
        return [
          'ok'     => false,
          'status' => $response->status(),
          'data'   => null,
          'error'  => 'Invalid JSON response from LLM provider.',
        ];
      }

      return [
        'ok'     => true,
        'status' => $response->status(),
        'data'   => $decoded,
        'error'  => null,
      ];
    } catch (\Throwable $e) {
      Log::error('AI Assistant HTTP exception', [
        'message' => $e->getMessage(),
        'model'   => $payload['model'] ?? null,
      ]);

      return [
        'ok'     => false,
        'status' => 500,
        'data'   => null,
        'error'  => $e->getMessage(),
      ];
    }
  }

  public function streamChat(array $payload, callable $onDelta): array
  {
    $url = config('services.ai_assistant.api_url');
    $key = config('services.ai_assistant.api_key');

    if (empty($url) || empty($key)) {
      return [
        'ok'     => false,
        'status' => 503,
        'data'   => null,
        'error'  => 'AI Assistant API URL or API key is not configured.',
      ];
    }

    $endpoint                  = rtrim($url, '/') . '/chat/completions';
    $payload['stream']         = true;
    $payload['stream_options'] = ['include_usage' => true];

    $contentBuffer   = '';
    $reasoningBuffer = '';
    $toolCallsBuffer = [];
    $usage           = [];
    $returnedModel   = $payload['model'] ?? null;
    $responseId      = null;
    $finishReason    = null;
    $cost            = 0.0;
    $rawNonSse       = '';
    $lineBuffer      = '';

    try {
      $ch = curl_init($endpoint);
      curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_HTTPHEADER     => [
          'Authorization: Bearer ' . $key,
          'Content-Type: application/json',
          'Accept: text/event-stream',
        ],
        CURLOPT_WRITEFUNCTION  => function ($curl, $chunk) use (
          &$lineBuffer,
          &$rawNonSse,
          &$contentBuffer,
          &$reasoningBuffer,
          &$toolCallsBuffer,
          &$usage,
          &$returnedModel,
          &$responseId,
          &$finishReason,
          &$cost,
          $onDelta
        ) {
          $rawNonSse  .= $chunk;
          $lineBuffer .= $chunk;

          while (($pos = strpos($lineBuffer, "\n")) !== false) {
            $line       = trim(substr($lineBuffer, 0, $pos));
            $lineBuffer = substr($lineBuffer, $pos + 1);

            if ($line === '' || str_starts_with($line, ':')) {
              continue;
            }

            if (!str_starts_with($line, 'data:')) {
              continue;
            }

            $jsonStr = trim(substr($line, 5));
            if ($jsonStr === '[DONE]') {
              continue;
            }

            $event = json_decode($jsonStr, true);
            if (!is_array($event)) {
              continue;
            }

            if (!empty($event['id'])) {
              $responseId = $event['id'];
            }

            if (!empty($event['model'])) {
              $returnedModel = $event['model'];
            }

            if (isset($event['usage']) && is_array($event['usage'])) {
              $usage = $event['usage'];
            }

            if (isset($event['cost'])) {
              $cost = (float) $event['cost'];
            }

            $choice = $event['choices'][0] ?? null;
            if (!is_array($choice)) {
              continue;
            }

            if (!empty($choice['finish_reason'])) {
              $finishReason = $choice['finish_reason'];
            }

            $delta = $choice['delta'] ?? [];
            if (!is_array($delta)) {
              continue;
            }

            $contentDelta   = $delta['content'] ?? '';
            $reasoningDelta = $delta['reasoning'] ?? $delta['reasoning_content'] ?? '';

            if ($contentDelta !== '') {
              $contentBuffer .= $contentDelta;
              $onDelta($contentDelta, '');
            }

            if ($reasoningDelta !== '') {
              $reasoningBuffer .= $reasoningDelta;
              $onDelta('', $reasoningDelta);
            }

            if (!empty($delta['tool_calls']) && is_array($delta['tool_calls'])) {
              foreach ($delta['tool_calls'] as $tcDelta) {
                $idx = (int) ($tcDelta['index'] ?? 0);
                if (!isset($toolCallsBuffer[$idx])) {
                  $toolCallsBuffer[$idx] = [
                    'id'       => $tcDelta['id'] ?? '',
                    'type'     => $tcDelta['type'] ?? 'function',
                    'function' => [
                      'name'      => $tcDelta['function']['name'] ?? '',
                      'arguments' => $tcDelta['function']['arguments'] ?? '',
                    ],
                  ];
                } else {
                  if (!empty($tcDelta['id'])) {
                    $toolCallsBuffer[$idx]['id'] = $tcDelta['id'];
                  }
                  if (!empty($tcDelta['function']['name'])) {
                    $toolCallsBuffer[$idx]['function']['name'] .= $tcDelta['function']['name'];
                  }
                  if (isset($tcDelta['function']['arguments'])) {
                    $toolCallsBuffer[$idx]['function']['arguments'] .= $tcDelta['function']['arguments'];
                  }
                }
              }
            }
          }

          return strlen($chunk);
        },
      ]);

      curl_exec($ch);
      $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlError = curl_error($ch);
      curl_close($ch);

      if ($curlError !== '') {
        Log::error('AI Assistant stream curl error', [
          'error' => $curlError,
          'model' => $payload['model'] ?? null,
        ]);

        return [
          'ok'     => false,
          'status' => $httpCode ?: 500,
          'data'   => null,
          'error'  => $curlError,
        ];
      }

      if ($httpCode < 200 || $httpCode >= 300) {
        Log::error('AI Assistant stream HTTP error', [
          'status' => $httpCode,
          'body'   => mb_substr($rawNonSse, 0, 500),
          'model'  => $payload['model'] ?? null,
        ]);

        return [
          'ok'     => false,
          'status' => $httpCode,
          'data'   => null,
          'error'  => "HTTP {$httpCode}: " . mb_substr($rawNonSse, 0, 500),
        ];
      }

      if ($contentBuffer === '' && empty($toolCallsBuffer) && str_starts_with(trim($rawNonSse), '{')) {
        $decoded = json_decode(trim($rawNonSse), true);
        if (is_array($decoded) && isset($decoded['choices'][0]['message'])) {
          $msgContent = (string) ($decoded['choices'][0]['message']['content'] ?? '');
          if ($msgContent !== '') {
            $onDelta($msgContent, '');
          }

          return [
            'ok'     => true,
            'status' => $httpCode,
            'data'   => $decoded,
            'error'  => null,
          ];
        }
      }

      ksort($toolCallsBuffer);
      $message = [
        'role'    => 'assistant',
        'content' => $contentBuffer !== '' ? $contentBuffer : null,
      ];

      if ($reasoningBuffer !== '') {
        $message['reasoning_content'] = $reasoningBuffer;
      }

      if (!empty($toolCallsBuffer)) {
        $message['tool_calls'] = array_values($toolCallsBuffer);
      }

      return [
        'ok'     => true,
        'status' => $httpCode,
        'data'   => [
          'id'      => $responseId,
          'model'   => $returnedModel,
          'usage'   => $usage,
          'cost'    => $cost,
          'choices' => [
            [
              'message'       => $message,
              'finish_reason' => $finishReason,
            ],
          ],
        ],
        'error'  => null,
      ];
    } catch (\Throwable $e) {
      Log::error('AI Assistant stream exception', [
        'message' => $e->getMessage(),
        'model'   => $payload['model'] ?? null,
      ]);

      return [
        'ok'     => false,
        'status' => 500,
        'data'   => null,
        'error'  => $e->getMessage(),
      ];
    }
  }
}
