<?php

namespace App\Services\AiAssistant\Tools;

interface AiAssistantToolInterface
{
  public function getName(): string;
  public function getDescription(): string;
  public function getParametersSchema(): array;
  public function execute(array $arguments, ?int $userId = null): mixed;
}
