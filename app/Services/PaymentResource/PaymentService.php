<?php

/*
 * Project Name: personal-v4
 * File: PaymentService.php
 * Created Date: Thursday December 11th 2025
 *
 * Author: Nova Ardiansyah admin@novaardiansyah.id
 * Website: https://novaardiansyah.id
 * MIT License: https://github.com/novaardiansyah/personal-v4/blob/main/LICENSE
 *
 * Copyright (c) 2026 Nova Ardiansyah, Org
 */

namespace App\Services\PaymentResource;

use App\Models\Item;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\PaymentType;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Log;

class PaymentService
{
	public static function afterItemAttach(Payment $payment, Item $item, array $data): array
	{
		$item->update(['amount' => $data['price'], 'updated_at' => now()]);

		PaymentItem::where('payment_id', $payment->id)
			->where('item_id', $item->id)
			->first()
			?->touch();

		$expense = $payment->amount + (int) $data['total'];
		$note = trim(($payment->name ?? '') . ', ' . "{$item->name} (x{$data['quantity']})", ', ');

		$payment->update(['amount' => $expense, 'name' => $note]);

		return [
			'success'          => true,
			'amount'           => $payment->amount,
			'formatted_amount' => toIndonesianCurrency($payment->amount),
			'note'             => $note,
		];
	}

	public static function beforeItemDetach(Payment $payment, Item $item, array $data): array
	{
		$expense = $payment->amount - (int) $data['total'];

		$itemName = $item->name . ' (x' . $data['quantity'] . ')';
		$note = trim(implode(', ', array_diff(explode(', ', $payment->name ?? ''), [$itemName])));

		$payment->update(['amount' => $expense, 'name' => $note]);

		return [
			'success'          => true,
			'amount'           => $payment->amount,
			'formatted_amount' => toIndonesianCurrency($payment->amount),
			'note'             => $note,
		];
	}

	public static function updateItemPivot(Payment $payment, Item $item, array $data): array
	{
		$oldQuantity = (int) $item->pivot->quantity;
		$oldTotal = (int) $item->pivot->total;
		$oldPrice = (int) $item->pivot->price;

		$newQuantity = (int) $data['quantity'];
		$newPrice = (int) $data['amount'];
		$newTotal = $newQuantity * $newPrice;

		$totalDiff = $newTotal - $oldTotal;
		$newPaymentAmount = $payment->amount + $totalDiff;

		$oldItemName = $item->name . ' (x' . $oldQuantity . ')';
		$newItemName = $item->name . ' (x' . $newQuantity . ')';
		$note = str_replace($oldItemName, $newItemName, $payment->name ?? '');

		$payment->update(['amount' => $newPaymentAmount, 'name' => $note]);
		$item->update(['amount' => $newPrice]);

		$properties = [
			'quantity' => $newQuantity,
			'price'    => $newPrice,
			'total'    => $newTotal,
		];

		PaymentItem::where('payment_id', $payment->id)
			->where('item_id', $item->id)
			->first()
			?->update($properties);

		return [
			'success'          => true,
			'amount'           => $payment->amount,
			'formatted_amount' => toIndonesianCurrency($payment->amount),
			'note'             => $note,
		];
	}


	public static function manageDraft(Payment $record, bool $is_draft): array
	{
		if ($is_draft) {
			$record->is_draft = true;
			$record->save();

			return ['status' => true, 'message' => 'Draft status has been updated.'];
		}

		if (!$record->is_draft) {
			return ['status' => false, 'message' => 'This transaction is not a draft or has already been approved.'];
		}

		$type_id = intval($record->type_id);
		$amount = intval($record->amount);
		$payment_account = $record->payment_account;
		$payment_account_to = $record->payment_account_to;

		if ($type_id == PaymentType::INCOME) {
			$payment_account->deposit += $amount;
		} else {
			if ($payment_account->deposit < $amount) {
				return ['status' => false, 'message' => 'The amount in the payment account is not sufficient for the transaction.'];
			}

			if ($type_id == PaymentType::EXPENSE) {
				$payment_account->deposit -= $amount;
			} else if ($type_id == PaymentType::TRANSFER || $type_id == PaymentType::WITHDRAWAL) {
				if (!$payment_account_to) {
					return ['status' => false, 'message' => 'The destination payment account is invalid or not found.'];
				}

				$payment_account->deposit -= $amount;
				$payment_account_to->deposit += $amount;
			} else {
				return ['status' => false, 'message' => 'The selected transaction type is invalid.'];
			}
		}

		if ($payment_account->isDirty('deposit')) {
			$payment_account->save();
		}

		if ($payment_account_to && $payment_account_to->isDirty('deposit')) {
			$payment_account_to->save();
		}

		$record->is_draft = false;
		$record->save();

		return ['status' => true, 'message' => 'Draft has been approved and balance has been mutated.'];
	}

	public static function make_pdf(array $data): array
	{
		Log::info('4236 --> PaymentService::make_pdf(): Started.');

		Carbon::setLocale('id');

		$notification       = $data['notification'] ?? false;
		$auto_close_tbody   = $data['auto_close_tbody'] ?? false;
		$payment_account_id = $data['payment_account_id'] ?? null;

		$startDate = $data['start_date'] ?? now()->startOfMonth();
		$endDate   = $data['end_date'] ?? now()->endOfMonth();
		$now       = $data['now'] ?? now()->toDateTimeString();

		$carbonStartDate = Carbon::parse($startDate);
		$carbonEndDate   = Carbon::parse($endDate);
		$periode         = '-';

		if ($carbonStartDate->isSameDay($carbonEndDate)) {
			$periode = $carbonStartDate->translatedFormat('d F Y');
		} else {
			$startFormat = $carbonStartDate->isSameMonth($carbonEndDate) ? 'd' : 'd F Y';
			$periode     = $carbonStartDate->translatedFormat($startFormat) . ' - ' . $carbonEndDate->translatedFormat('d F Y');
		}

		// ! Setup pdf attachment
		$mpdf          = new \Mpdf\Mpdf();
		$rowIndex      = 1;
		$totalExpense  = 0;
		$totalIncome   = 0;
		$totalTransfer = 0;
		$user          = $data['user'] ?? getUser();

		$mpdf->WriteHTML(view('payment-resource.make-pdf.header', [
			'title'   => $data['title'] ?? 'Laporan keuangan',
			'now'     => carbonTranslatedFormat($now, 'l, d M Y, H.i', 'id') . ' WIB',
			'periode' => $periode,
			'user'    => $user,
		])->render());

		Payment::whereBetween('date', [$startDate, $endDate])
			->orderBy('date', 'desc')
			->when($payment_account_id, function ($query) use ($payment_account_id) {
				$query->where('payment_account_id', $payment_account_id);
			})
			->chunk(200, function ($list) use ($mpdf, &$rowIndex, &$totalExpense, &$totalIncome, &$totalTransfer) {
				foreach ($list as $record) {
					$record->income  = (int) $record->type_id === PaymentType::INCOME ? $record->amount : 0;
					$record->expense = (int) $record->type_id === PaymentType::EXPENSE ? $record->amount : 0;

					if ((int) $record->type_id === PaymentType::INCOME || (int) $record->type_id === PaymentType::EXPENSE) {
						$record->amount = 0;
					}

					$view = view('payment-resource.make-pdf.body', [
						'record'    => $record,
						'loopIndex' => $rowIndex++,
					])->render();

					$mpdf->WriteHTML($view);

					if ((int) $record->type_id === PaymentType::EXPENSE) {
						$totalExpense += $record->expense;
					} elseif ((int) $record->type_id === PaymentType::INCOME) {
						$totalIncome += $record->income;
					} else {
						$totalTransfer += $record->amount;
					}
				}
			});

		$mpdf->WriteHTML('
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" style="text-align: center; font-weight: bold;">Total Transaksi</td>
          <td style="font-weight: bold;">' . toIndonesianCurrency($totalTransfer) . '</td>
          <td style="font-weight: bold;">' . toIndonesianCurrency($totalIncome) . '</td>
          <td style="font-weight: bold;">' . toIndonesianCurrency($totalExpense) . '</td>
        </tr>
      </tfoot>
    ');

		$result = makePdf($mpdf, $user, notification: $notification, auto_close_tbody: $auto_close_tbody);

		Log::info('4236 --> PaymentService::make_pdf(): Finished.');

		return $result;
	}

	public static function scheduledPayment(): array
	{
		$today = Carbon::now()->format('Y-m-d');
		$tomorrow = Carbon::now()->addDay()->format('Y-m-d');

		$scheduledPayments = Payment::where('is_scheduled', true)
			->whereBetween('date', [$today, $tomorrow])
			->orderBy('type_id', 'desc')
			->get();

		if ($scheduledPayments->isEmpty()) {
			return ['status' => false, 'message' => 'No scheduled payments found for today.'];
		}

		$reports = [];
		$scheduledPayments->each(function (Payment $payment) use (&$reports) {
			$report = self::processScheduledPayment($payment);
			if ($report['status'] == false) {
				$reports[] = $report['message'];
			}
		});

		return ['status' => true, 'message' => 'Scheduled payments processed successfully.', 'reports' => $reports];
	}

	public static function processScheduledPayment(Payment $payment): array
	{
		$record = $payment;
		$type_id = intval($record->type_id);
		$amount = intval($record->amount);

		$incomeOrExpense = $type_id == PaymentType::EXPENSE || $type_id == PaymentType::INCOME;
		$transferOrWithdrawal = $type_id == PaymentType::TRANSFER || $type_id == PaymentType::WITHDRAWAL;

		if ($incomeOrExpense) {
			$depositChange = $record->payment_account->deposit;

			if ($type_id == PaymentType::EXPENSE) {
				if ($depositChange < $amount) {
					return ['status' => false, 'message' => 'Insufficient account balance for transaction: ' . $record->code];
				}
				$depositChange -= $amount;
			} else {
				$depositChange += $amount;
			}

			$record->payment_account->update([
				'deposit' => $depositChange
			]);
		} else if ($transferOrWithdrawal) {
			$balanceOrigin = $record->payment_account->deposit;
			$balanceTo = $record->payment_account_to->deposit;

			if ($balanceOrigin < $amount) {
				return ['status' => false, 'message' => 'Insufficient account balance for transaction: ' . $record->code];
			}

			$record->payment_account->update([
				'deposit' => $balanceOrigin - $amount
			]);

			$record->payment_account_to->update([
				'deposit' => $balanceTo + $amount
			]);
		}

		$payment->is_scheduled = false;
		$payment->save();

		return ['status' => true, 'message' => 'Scheduled payment processed successfully.'];
	}
}
