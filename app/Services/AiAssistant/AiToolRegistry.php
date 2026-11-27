<?php

namespace App\Services\AiAssistant;

use App\Services\AiAssistant\Tools\AiAssistantToolInterface;
use App\Services\AiAssistant\Tools\CreateCalendarEventTool;
use App\Services\AiAssistant\Tools\CreateCalendarTodoTool;
use App\Services\AiAssistant\Tools\CreateDraftPaymentTool;
use App\Services\AiAssistant\Tools\CreateNoteTool;
use App\Services\AiAssistant\Tools\GenerateFinancialReportPdfTool;
use App\Services\AiAssistant\Tools\GetActivityLogsTool;
use App\Services\AiAssistant\Tools\GetBackupStatusTool;
use App\Services\AiAssistant\Tools\GetBlogInsightsTool;
use App\Services\AiAssistant\Tools\GetCalendarScheduleTool;
use App\Services\AiAssistant\Tools\GetCalendarTodosTool;
use App\Services\AiAssistant\Tools\GetDebtsOverviewTool;
use App\Services\AiAssistant\Tools\GetPaymentBalancesTool;
use App\Services\AiAssistant\Tools\GetPaymentGoalsTool;
use App\Services\AiAssistant\Tools\GetPaymentItemsTool;
use App\Services\AiAssistant\Tools\GetPaymentSummaryTool;
use App\Services\AiAssistant\Tools\GetPaymentTransactionsTool;
use App\Services\AiAssistant\Tools\GetRecentCommunicationsTool;
use App\Services\AiAssistant\Tools\GetSubscriptionsTool;
use App\Services\AiAssistant\Tools\GetUptimeStatusTool;
use App\Services\AiAssistant\Tools\SearchFilesTool;
use App\Services\AiAssistant\Tools\SearchNotesTool;

class AiToolRegistry
{
  protected array $tools = [];

  public function __construct()
  {
    $this->register(new GetPaymentTransactionsTool());
    $this->register(new GetPaymentBalancesTool());
    $this->register(new GetPaymentSummaryTool());
    $this->register(new GetPaymentGoalsTool());
    $this->register(new GetDebtsOverviewTool());
    $this->register(new GetSubscriptionsTool());
    $this->register(new GetPaymentItemsTool());
    $this->register(new GetCalendarScheduleTool());
    $this->register(new GetCalendarTodosTool());
    $this->register(new SearchNotesTool());
    $this->register(new SearchFilesTool());
    $this->register(new GetUptimeStatusTool());
    $this->register(new GetBackupStatusTool());
    $this->register(new GetRecentCommunicationsTool());
    $this->register(new GetBlogInsightsTool());
    $this->register(new GetActivityLogsTool());
    $this->register(new CreateDraftPaymentTool());
    $this->register(new CreateCalendarTodoTool());
    $this->register(new CreateCalendarEventTool());
    $this->register(new CreateNoteTool());
    $this->register(new GenerateFinancialReportPdfTool());
  }

  public function register(AiAssistantToolInterface $tool): void
  {
    $this->tools[$tool->getName()] = $tool;
  }

  public function getToolNames(): array
  {
    return array_keys($this->tools);
  }

  public function getToolOptions(): array
  {
    $options = [];
    foreach ($this->tools as $name => $tool) {
      $options[$name] = "{$name} — {$tool->getDescription()}";
    }
    return $options;
  }

  public function getToolsSchema(?array $allowedTools = null): array
  {
    if (is_array($allowedTools) && count($allowedTools) === 0) {
      return [];
    }

    $schemas = [];

    foreach ($this->tools as $name => $tool) {
      if (is_array($allowedTools) && !in_array($name, $allowedTools, true)) {
        continue;
      }

      $schemas[] = [
        'type'     => 'function',
        'function' => [
          'name'        => $tool->getName(),
          'description' => $tool->getDescription(),
          'parameters'  => $tool->getParametersSchema(),
        ],
      ];
    }

    return $schemas;
  }

  public function getTool(string $name): ?AiAssistantToolInterface
  {
    return $this->tools[$name] ?? null;
  }

  public function executeTool(string $name, array $arguments, ?int $userId = null): mixed
  {
    if (!isset($this->tools[$name])) {
      return ['status' => false, 'message' => "Tool {$name} not found"];
    }

    return $this->tools[$name]->execute($arguments, $userId);
  }
}
