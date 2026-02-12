<?php

/*
 * Project Name: personal-v4
 * File: UptimeMonitorService.php
 * Created Date: Saturday February 8th 2026
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

declare(strict_types=1);

namespace App\Services\UptimeMonitorResource;

use App\Enums\UptimeMonitorStatus;
use App\Models\HttpStatus;
use App\Models\UptimeMonitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class UptimeMonitorService
{
  private const SLOW_RESPONSE_THRESHOLD_MS = 300;
  private const HTTP_TIMEOUT_SECONDS = 30;

  public function check(UptimeMonitor $monitor): bool
  {
    $result = $this->performHttpCheck($monitor->url);
    $result = $this->evaluateSlowResponse($result);
    $result = $this->generateErrorMessage($result);

    $this->createLog($monitor, $result);
    $originalLastUnhealthyAt = $monitor->last_unhealthy_at;
    $this->updateMonitorStats($monitor, $result['is_healthy'], $result['status']);
    $this->sendStatusNotification($monitor, $result['is_healthy'], $result['error_message'], $originalLastUnhealthyAt);

    $monitor->save();

    return $result['is_healthy'];
  }

  private function performHttpCheck(string $url): array
  {
    $statusCode   = null;
    $responseTime = 0;
    $errorMessage = null;
    $isHealthy    = false;

    $startTime = microtime(true);

    try {
      $response   = Http::timeout(self::HTTP_TIMEOUT_SECONDS)->get($url);
      $statusCode = $response->status();
      $isHealthy  = $response->successful();
    } catch (ConnectionException $e) {
      $statusCode = 408;
    } catch (\Throwable $e) {
      $statusCode = 500;
    } finally {
      $responseTime = (int) round((microtime(true) - $startTime) * 1000);
    }

    return compact('statusCode', 'responseTime', 'errorMessage', 'isHealthy');
  }

  private function evaluateSlowResponse(array $result): array
  {
    $status = UptimeMonitorStatus::UP;

    if ($result['isHealthy'] && $result['responseTime'] > self::SLOW_RESPONSE_THRESHOLD_MS) {
      $status = UptimeMonitorStatus::SLOW;
    } elseif (!$result['isHealthy']) {
      $status = UptimeMonitorStatus::DOWN;
    }

    return [
      'status_code'      => $result['statusCode'],
      'response_time_ms' => $result['responseTime'],
      'is_healthy'       => $result['isHealthy'],
      'status'           => $status,
    ];
  }

  private function generateErrorMessage(array $result): array
  {
    if ($result['is_healthy']) {
      $result['error_message'] = null;
      return $result;
    }

    $statusCode = $result['status_code'];
    $httpStatus = HttpStatus::where('name', $statusCode)->first();
    $errorMessage = "HTTP {$statusCode}";

    if ($httpStatus) {
      $errorMessage .= " {$httpStatus->message} ({$httpStatus->description})";
    }

    $result['error_message'] = $errorMessage;
    return $result;
  }

  private function createLog(UptimeMonitor $monitor, array $result): void
  {
    $monitor->logs()->create([
      'status_code'      => $result['status_code'],
      'response_time_ms' => $result['response_time_ms'],
      'is_healthy'       => $result['is_healthy'],
      'error_message'    => $result['error_message'],
      'checked_at'       => now(),
    ]);
  }

  private function updateMonitorStats(UptimeMonitor $monitor, bool $isHealthy, UptimeMonitorStatus $status): void
  {
    $monitor->status           = $status;
    $monitor->total_checks     = ($monitor->total_checks ?? 0) + 1;
    $monitor->last_checked_at  = now();
    $monitor->next_check_at    = now()->addSeconds($monitor->interval);

    $isHealthy
      ? $monitor->healthy_checks = ($monitor->healthy_checks ?? 0) + 1
      : $monitor->unhealthy_checks = ($monitor->unhealthy_checks ?? 0) + 1;

    $isHealthy
      ? $monitor->last_healthy_at = now()
      : $monitor->last_unhealthy_at = now();
  }

  private function sendStatusNotification(UptimeMonitor $monitor, bool $isHealthy, ?string $errorMessage, $originalLastUnhealthyAt): void
  {
    $previousStatus = $monitor->getOriginal('status');
    $previousValue  = $previousStatus instanceof UptimeMonitorStatus ? $previousStatus->value : $previousStatus;

    $wasDown = $previousValue === UptimeMonitorStatus::DOWN->value;
    $wasSlow = $previousValue === UptimeMonitorStatus::SLOW->value;

    $isDown = $monitor->status === UptimeMonitorStatus::DOWN;
    $isSlow = $monitor->status === UptimeMonitorStatus::SLOW;
    $isUp   = $monitor->status === UptimeMonitorStatus::UP;

    if ($isDown && !$wasDown) {
      $this->sendDownNotification($monitor, $errorMessage);
    }

    if ($isUp && ($wasDown || $wasSlow)) {
      $this->sendUpNotification($monitor, $originalLastUnhealthyAt);
    }

    if ($isSlow && !$wasSlow) {
      $this->sendSlowNotification($monitor);
    }
  }

  private function sendDownNotification(UptimeMonitor $monitor, ?string $errorMessage): void
  {
    $message = "🔴 {$monitor->name} is now DOWN\n";
    $message .= "Target: {$monitor->url}\n";
    $message .= "Noticed at: " . now()->format('M j, Y H:i:s') . "\n";
    $message .= "Encountered errors: " . $errorMessage;

    sendTelegramNotification($message);
  }

  private function sendUpNotification(UptimeMonitor $monitor, $originalLastUnhealthyAt): void
  {
    $downtime = $originalLastUnhealthyAt?->diffForHumans(now(), ['parts' => 2, 'short' => true]) ?? 'unknown';
    $message = "🟢 {$monitor->name} is now UP\n";
    $message .= "Downtime: {$downtime}\n";
    $message .= "Target: {$monitor->url}\n";
    $message .= "Noticed at: " . now()->format('M j, Y H:i:s');
    sendTelegramNotification($message);
  }

  private function sendSlowNotification(UptimeMonitor $monitor): void
  {
    $message = "🟡 {$monitor->name} is SLOW\n";
    $message .= "Target: {$monitor->url}\n";
    $message .= "Response Time: {$monitor->logs()->latest('checked_at')->first()?->response_time_ms} ms\n";
    $message .= "Threshold: " . self::SLOW_RESPONSE_THRESHOLD_MS . " ms\n";
    $message .= "Noticed at: " . now()->format('M j, Y H:i:s');

    sendTelegramNotification($message);
  }

  public function runScheduledChecks(): array
  {
    $results = [
      'total'     => 0,
      'healthy'   => 0,
      'unhealthy' => 0,
    ];

    UptimeMonitor::query()
      ->where('is_active', true)
      ->where(function ($query) {
        $query->whereNull('next_check_at')
          ->orWhere('next_check_at', '<=', now());
      })
      ->chunkById(50, function ($monitors) use (&$results) {
        foreach ($monitors as $monitor) {
          $results['total']++;

          if ($this->check($monitor)) {
            $results['healthy']++;
          } else {
            $results['unhealthy']++;
          }
        }
      });

    return $results;
  }
}
