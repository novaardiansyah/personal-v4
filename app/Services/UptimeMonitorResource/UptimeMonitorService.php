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

use App\Models\UptimeMonitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class UptimeMonitorService
{
  private const SLOW_RESPONSE_THRESHOLD_MS = 3000;
  private const HTTP_TIMEOUT_SECONDS = 30;

  public function check(UptimeMonitor $monitor): bool
  {
    $result = $this->performHttpCheck($monitor->url);
    $result = $this->evaluateSlowResponse($result);

    $this->createLog($monitor, $result);
    $this->updateMonitorStats($monitor, $result['is_healthy']);
    $this->sendStatusNotification($monitor, $result['is_healthy'], $result['status_code'], $result['error_message']);

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
      $statusCode   = 408;
      $errorMessage = $e->getMessage();
    } catch (\Throwable $e) {
      $statusCode   = 500;
      $errorMessage = $e->getMessage();
    } finally {
      $responseTime = (int) round((microtime(true) - $startTime) * 1000);
    }

    return compact('statusCode', 'responseTime', 'errorMessage', 'isHealthy');
  }

  private function evaluateSlowResponse(array $result): array
  {
    if ($result['isHealthy'] && $result['responseTime'] > self::SLOW_RESPONSE_THRESHOLD_MS) {
      $result['isHealthy']    = false;
      $result['statusCode']   = 503;
      $result['errorMessage'] = 'Service degraded: Response time exceeded acceptable threshold (' . $result['responseTime'] . 'ms). Performance optimization required.';
    }

    return [
      'status_code'      => $result['statusCode'],
      'response_time_ms' => $result['responseTime'],
      'error_message'    => $result['errorMessage'],
      'is_healthy'       => $result['isHealthy'],
    ];
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

  private function updateMonitorStats(UptimeMonitor $monitor, bool $isHealthy): void
  {
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

  private function sendStatusNotification(UptimeMonitor $monitor, bool $isHealthy, ?int $statusCode, ?string $errorMessage): void
  {
    $wasUnhealthy = $monitor->getOriginal('last_unhealthy_at') > $monitor->getOriginal('last_healthy_at');

    if (!$isHealthy && !$wasUnhealthy) {
      $this->sendDownNotification($monitor, $statusCode, $errorMessage);
    }

    if ($isHealthy && $wasUnhealthy) {
      $this->sendUpNotification($monitor);
    }
  }

  private function sendDownNotification(UptimeMonitor $monitor, ?int $statusCode, ?string $errorMessage): void
  {
    $message = "🔴 {$monitor->name} is now DOWN\n";
    $message .= "Target: {$monitor->url}\n";
    $message .= "Noticed at: " . now()->format('M j, Y H:i:s') . "\n";
    $message .= "Encountered errors: " . ($errorMessage ?? "HTTP {$statusCode}");
    sendTelegramNotification($message);
  }

  private function sendUpNotification(UptimeMonitor $monitor): void
  {
    $downtime = $monitor->last_unhealthy_at->diffForHumans(now(), ['parts' => 2, 'short' => true]);
    $message = "🟢 {$monitor->name} is now UP\n";
    $message .= "Downtime: {$downtime}\n";
    $message .= "Target: {$monitor->url}\n";
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
