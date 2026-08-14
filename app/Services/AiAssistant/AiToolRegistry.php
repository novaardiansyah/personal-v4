<?php

namespace App\Services\AiAssistant;

use App\Services\AiAssistant\Tools\AiAssistantToolInterface;
use App\Services\AiAssistant\Tools\GetPaymentBalancesTool;
use App\Services\AiAssistant\Tools\GetPaymentTransactionsTool;

class AiToolRegistry
{
  protected array $tools = [];

  public function __construct()
  {
    $this->register(new GetPaymentTransactionsTool());
    $this->register(new GetPaymentBalancesTool());
  }

  public function register(AiAssistantToolInterface $tool): void
  {
    $this->tools[$tool->getName()] = $tool;
  }

  public function getToolsSchema(): array
  {
    $schemas = [];

    foreach ($this->tools as $tool) {
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

  public function executeTool(string $name, array $arguments, ?int $userId = null): mixed
  {
    if (!isset($this->tools[$name])) {
      return ['status' => false, 'message' => "Tool {$name} not found"];
    }

    return $this->tools[$name]->execute($arguments, $userId);
  }
}
