<?php

use App\Models\Debt;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\User;

test('it creates installments with correct due dates and paid at dates for partial payment', function () {
  $user = User::find(4);
  assert($user instanceof User);
  $this->actingAs($user);

  $debt = Debt::create([
    'user_id'             => 4,
    'payment_account_id'  => 1,
    'platform_name'       => 'Test Platform',
    'name'                => 'Test Debt',
    'principal_amount'    => 3000000,
    'admin_fee'           => 0,
    'disbursement_amount' => 3000000,
    'interest_rate'       => 2.95,
    'service_fee_rate'    => 1.0,
    'tenor'               => 3,
    'start_date'          => '2026-05-01',
    'status'              => 'partial_payment',
    'paid_tenor'          => 2,
  ]);

  expect($debt->installments)->toHaveCount(3);

  $installments = $debt->installments()->orderBy('installment_number')->get();

  expect($installments[0]->due_date->format('Y-m-d'))->toBe('2026-05-01');
  expect($installments[1]->due_date->format('Y-m-d'))->toBe('2026-06-01');
  expect($installments[2]->due_date->format('Y-m-d'))->toBe('2026-07-01');

  expect($installments[0]->status)->toBe('paid');
  expect($installments[1]->status)->toBe('paid');
  expect($installments[2]->status)->toBe('unpaid');

  expect($installments[0]->paid_at->format('Y-m-d'))->toBe('2026-05-01');
  expect($installments[1]->paid_at->format('Y-m-d'))->toBe('2026-06-01');
  expect($installments[2]->paid_at)->toBeNull();

  $disbursementPayment = Payment::where('user_id', $user->id)
    ->where('type_id', PaymentType::INCOME)
    ->where('name', $debt->platform_name . ' - ' . $debt->name . ' (' . $debt->code . ')')
    ->first();

  expect($disbursementPayment)->not->toBeNull();
  expect($disbursementPayment->is_draft)->toBeTrue();
  expect($disbursementPayment->amount)->toBe(3000000);
  expect($disbursementPayment->date)->toBe('2026-05-01');

  $draftPayments = Payment::where('user_id', $user->id)
    ->where('type_id', PaymentType::EXPENSE)
    ->where('name', $debt->platform_name . ' - ' . $debt->name . ' (' . $debt->code . ')')
    ->orderBy('date')
    ->get();

  expect($draftPayments)->toHaveCount(2);

  expect($draftPayments[0]->is_draft)->toBeTrue();
  expect($draftPayments[0]->date)->toBe('2026-05-01');
  expect($draftPayments[0]->amount)->toBe($installments[0]->total_amount);

  expect($draftPayments[1]->is_draft)->toBeTrue();
  expect($draftPayments[1]->date)->toBe('2026-06-01');
  expect($draftPayments[1]->amount)->toBe($installments[1]->total_amount);

  expect($installments[0]->payment_id)->toBe($draftPayments[0]->id);
  expect($installments[1]->payment_id)->toBe($draftPayments[1]->id);
});

test('it creates installments with correct due dates and paid at dates for full paid status', function () {
  $user = User::find(4);
  assert($user instanceof User);
  $this->actingAs($user);

  $debt = Debt::create([
    'user_id'             => 4,
    'payment_account_id'  => 1,
    'platform_name'       => 'Test Platform',
    'name'                => 'Test Debt Paid',
    'principal_amount'    => 3000000,
    'admin_fee'           => 0,
    'disbursement_amount' => 3000000,
    'interest_rate'       => 2.95,
    'service_fee_rate'    => 1.0,
    'tenor'               => 3,
    'start_date'          => '2026-05-01',
    'status'              => 'paid',
  ]);

  expect($debt->installments)->toHaveCount(3);

  $installments = $debt->installments()->orderBy('installment_number')->get();

  expect($installments[0]->status)->toBe('paid');
  expect($installments[1]->status)->toBe('paid');
  expect($installments[2]->status)->toBe('paid');

  expect($installments[0]->paid_at->format('Y-m-d'))->toBe('2026-05-01');
  expect($installments[1]->paid_at->format('Y-m-d'))->toBe('2026-06-01');
  expect($installments[2]->paid_at->format('Y-m-d'))->toBe('2026-07-01');

  $draftPayments = Payment::where('user_id', $user->id)
    ->where('type_id', PaymentType::EXPENSE)
    ->where('name', $debt->platform_name . ' - ' . $debt->name . ' (' . $debt->code . ')')
    ->orderBy('date')
    ->get();

  expect($draftPayments)->toHaveCount(3);

  expect($draftPayments[0]->date)->toBe('2026-05-01');
  expect($draftPayments[1]->date)->toBe('2026-06-01');
  expect($draftPayments[2]->date)->toBe('2026-07-01');

  expect($installments[0]->payment_id)->toBe($draftPayments[0]->id);
  expect($installments[1]->payment_id)->toBe($draftPayments[1]->id);
  expect($installments[2]->payment_id)->toBe($draftPayments[2]->id);
});

test('it creates installments with correct due dates and paid at dates for ongoing status', function () {
  $user = User::find(4);
  assert($user instanceof User);
  $this->actingAs($user);

  $debt = Debt::create([
    'user_id'             => 4,
    'payment_account_id'  => 1,
    'platform_name'       => 'Test Platform',
    'name'                => 'Test Debt Ongoing',
    'principal_amount'    => 3000000,
    'admin_fee'           => 0,
    'disbursement_amount' => 3000000,
    'interest_rate'       => 2.95,
    'service_fee_rate'    => 1.0,
    'tenor'               => 3,
    'start_date'          => '2026-05-01',
    'status'              => 'ongoing',
  ]);

  expect($debt->installments)->toHaveCount(3);

  $installments = $debt->installments()->orderBy('installment_number')->get();

  expect($installments[0]->status)->toBe('unpaid');
  expect($installments[1]->status)->toBe('unpaid');
  expect($installments[2]->status)->toBe('unpaid');

  expect($installments[0]->paid_at)->toBeNull();
  expect($installments[1]->paid_at)->toBeNull();
  expect($installments[2]->paid_at)->toBeNull();

  $disbursementPayment = Payment::where('user_id', $user->id)
    ->where('type_id', PaymentType::INCOME)
    ->where('name', $debt->platform_name . ' - ' . $debt->name . ' (' . $debt->code . ')')
    ->first();

  expect($disbursementPayment)->not->toBeNull();
  expect($disbursementPayment->is_draft)->toBeTrue();
  expect($disbursementPayment->amount)->toBe(3000000);
  expect($disbursementPayment->date)->toBe('2026-05-01');

  $draftPayments = Payment::where('user_id', $user->id)
    ->where('type_id', PaymentType::EXPENSE)
    ->where('name', $debt->platform_name . ' - ' . $debt->name . ' (' . $debt->code . ')')
    ->orderBy('date')
    ->get();

  expect($draftPayments)->toHaveCount(0);

  expect($installments[0]->payment_id)->toBeNull();
  expect($installments[1]->payment_id)->toBeNull();
  expect($installments[2]->payment_id)->toBeNull();
});

