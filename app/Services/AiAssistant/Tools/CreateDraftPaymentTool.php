<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Payment;
use App\Models\PaymentAccount;
use Illuminate\Support\Carbon;

class CreateDraftPaymentTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'create_draft_payment';
  }

  public function getDescription(): string
  {
    return 'Propose creating a draft payment transaction (expense or income). Requires user confirmation before saving.';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [
        'name'                 => [
          'type'        => 'string',
          'description' => 'Transaction description/title.',
        ],
        'amount'               => [
          'type'        => 'number',
          'description' => 'Transaction amount in IDR.',
        ],
        'type'                 => [
          'type'        => 'string',
          'enum'        => ['expense', 'income'],
          'description' => 'Transaction type: expense or income.',
        ],
        'date'                 => [
          'type'        => 'string',
          'description' => 'Transaction date (YYYY-MM-DD). Defaults to today.',
        ],
        'payment_account_name' => [
          'type'        => 'string',
          'description' => 'Optional payment account name to match.',
        ],
      ],
      'required'   => ['name', 'amount', 'type'],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $name      = trim((string) ($arguments['name'] ?? ''));
    $amount    = (int) round((float) ($arguments['amount'] ?? 0));
    $typeStr   = strtolower((string) ($arguments['type'] ?? 'expense'));
    $date      = !empty($arguments['date']) ? Carbon::parse($arguments['date'])->format('Y-m-d') : now()->format('Y-m-d');
    $formatted = toIndonesianCurrency($amount);

    if ($name === '' || $amount <= 0) {
      return ['status' => false, 'message' => 'Name and positive amount are required.'];
    }

    return [
      'status'                => true,
      'requires_confirmation' => true,
      'summary'               => "Create draft {$typeStr}: \"{$name}\" for {$formatted} on {$date}",
      'proposed_data'         => [
        'name'                 => $name,
        'amount'               => $amount,
        'type'                 => $typeStr,
        'date'                 => $date,
        'payment_account_name' => $arguments['payment_account_name'] ?? null,
      ],
    ];
  }

  public function executeConfirmed(array $arguments, ?int $userId = null): array
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $typeStr = strtolower((string) ($arguments['type'] ?? 'expense'));
    $typeId  = $typeStr === 'income' ? 2 : 1;

    $accountQuery = PaymentAccount::where('user_id', $userId);
    if (!empty($arguments['payment_account_name'])) {
      $accountQuery->where('name', 'like', '%' . $arguments['payment_account_name'] . '%');
    }
    $account = $accountQuery->first() ?? PaymentAccount::where('user_id', $userId)->first();

    $payment = Payment::create([
      'user_id'            => $userId,
      'type_id'            => $typeId,
      'payment_account_id' => $account?->id,
      'name'               => trim((string) $arguments['name']),
      'amount'             => (int) round((float) $arguments['amount']),
      'date'               => !empty($arguments['date']) ? Carbon::parse($arguments['date'])->format('Y-m-d') : now()->format('Y-m-d'),
      'is_draft'           => true,
      'is_scheduled'       => false,
      'has_items'          => false,
    ]);

    return [
      'status'  => true,
      'message' => "Draft payment {$payment->code} ({$payment->name}) created.",
      'record'  => [
        'id'     => $payment->id,
        'code'   => $payment->code,
        'name'   => $payment->name,
        'amount' => toIndonesianCurrency((float) $payment->amount),
      ],
    ];
  }
}
