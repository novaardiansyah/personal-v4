<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\PaymentAccount;

class GetPaymentBalancesTool implements AiAssistantToolInterface
{
  public function getName(): string
  {
    return 'get_payment_balances';
  }

  public function getDescription(): string
  {
    return 'Retrieve current balance breakdown across all user payment accounts (bank accounts, e-wallets, cash).';
  }

  public function getParametersSchema(): array
  {
    return [
      'type'       => 'object',
      'properties' => [],
      'required'   => [],
    ];
  }

  public function execute(array $arguments, ?int $userId = null): mixed
  {
    $user   = getUser($userId);
    $userId = $user?->id;

    if (!$userId) {
      return ['status' => false, 'message' => 'User not authenticated'];
    }

    $accounts = PaymentAccount::where('user_id', $userId)->get();
    $totalBalance = (float) $accounts->sum('deposit');

    $accountList = $accounts->map(function ($acc) {
      return [
        'id'                => $acc->id,
        'name'              => $acc->name,
        'deposit'           => (float) $acc->deposit,
        'formatted_deposit' => toIndonesianCurrency((float) $acc->deposit),
      ];
    })->toArray();

    return [
      'total_balance'           => $totalBalance,
      'formatted_total_balance' => toIndonesianCurrency($totalBalance),
      'accounts'                => $accountList,
    ];
  }
}
