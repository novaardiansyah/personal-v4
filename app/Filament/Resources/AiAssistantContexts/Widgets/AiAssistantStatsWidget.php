<?php

namespace App\Filament\Resources\AiAssistantContexts\Widgets;

use App\Models\AiAssistantMessage;
use App\Models\AiAssistantSession;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiAssistantStatsWidget extends BaseWidget
{
  protected static bool $isDiscovered = false;

  protected function getStats(): array
  {
    $totalSessions = AiAssistantSession::count();
    $totalTokens   = (int) AiAssistantMessage::where('created_at', '>=', now()->subDays(30))->sum('token_total');
    $totalCost     = (float) AiAssistantMessage::where('created_at', '>=', now()->subDays(30))->sum('cost');
    $avgLatency    = (int) round((float) AiAssistantMessage::where('role', 'assistant')
      ->where('status', 'completed')
      ->where('latency_ms', '>', 0)
      ->where('created_at', '>=', now()->subDays(30))
      ->avg('latency_ms'));

    return [
      Stat::make('Total Sessions', number_format($totalSessions))
        ->description('All recorded AI conversations')
        ->icon(Heroicon::OutlinedChatBubbleLeftRight)
        ->color('primary'),

      Stat::make('30d Token Usage', number_format($totalTokens))
        ->description('Prompt + completion tokens (30 days)')
        ->icon(Heroicon::OutlinedBolt)
        ->color('info'),

      Stat::make('30d Estimated Cost', '$' . number_format($totalCost, 4))
        ->description('Cumulative API spend (30 days)')
        ->icon(Heroicon::OutlinedCurrencyDollar)
        ->color('warning'),

      Stat::make('Avg Response Latency', number_format($avgLatency / 1000, 2) . 's')
        ->description('Completed assistant turns (30 days)')
        ->icon(Heroicon::OutlinedClock)
        ->color('success'),
    ];
  }
}
