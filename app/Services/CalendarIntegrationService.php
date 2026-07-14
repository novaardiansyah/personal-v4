<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Payment;
use App\Models\DebtInstallment;
use App\Models\Note;
use Carbon\Carbon;

class CalendarIntegrationService
{
  public function syncFromPayment(Payment $payment): ?CalendarEvent
  {
    if ($payment->is_draft || $payment->is_scheduled) {
      return null;
    }

    $existing = CalendarEvent::where('source_type', 'payment')
      ->where('source_id', $payment->id)
      ->first();

    $eventData = [
      'user_id'       => $payment->user_id,
      'title'         => 'Payment: ' . ($payment->name ?? 'Payment'),
      'description'   => $payment->name ?? null,
      'start_at'      => Carbon::parse($payment->date)->startOfDay(),
      'end_at'        => Carbon::parse($payment->date)->endOfDay(),
      'is_all_day'    => true,
      'color'         => '#10B981',
      'source_type'   => 'payment',
      'source_id'     => $payment->id,
      'metadata'      => [
        'amount'  => $payment->amount,
        'type_id' => $payment->type_id,
      ],
    ];

    if ($existing) {
      $existing->update($eventData);
      return $existing;
    }

    $eventData['code'] = getCode('calendar_event');
    return CalendarEvent::create($eventData);
  }

  public function syncFromDebtInstallment(DebtInstallment $installment): ?CalendarEvent
  {
    if ($installment->paid_at) {
      $this->removeSource('debt', $installment->id);
      return null;
    }

    $existing = CalendarEvent::where('source_type', 'debt')
      ->where('source_id', $installment->id)
      ->first();

    $debt = $installment->debt;

    $eventData = [
      'user_id'       => $debt->user_id,
      'title'         => 'Debt Installment: ' . $debt->name,
      'description'   => 'Installment #' . $installment->installment_number . ' - ' . $debt->name,
      'start_at'      => $installment->due_date->startOfDay(),
      'end_at'        => $installment->due_date->endOfDay(),
      'is_all_day'    => true,
      'color'         => '#EF4444',
      'source_type'   => 'debt',
      'source_id'     => $installment->id,
      'metadata'      => [
        'total_amount'           => $installment->total_amount,
        'installment_number'     => $installment->installment_number,
        'debt_id'                => $debt->id,
      ],
    ];

    if ($existing) {
      $existing->update($eventData);
      return $existing;
    }

    $eventData['code'] = getCode('calendar_event');
    return CalendarEvent::create($eventData);
  }

  public function syncFromNote(Note $note): ?CalendarEvent
  {
    if (!$note->is_pinned) {
      return null;
    }

    $existing = CalendarEvent::where('source_type', 'note')
      ->where('source_id', $note->id)
      ->first();

    $eventData = [
      'user_id'       => $note->user_id,
      'title'         => 'Note: ' . $note->title,
      'description'   => $note->content,
      'start_at'      => $note->created_at->startOfDay(),
      'end_at'        => $note->created_at->endOfDay(),
      'is_all_day'    => true,
      'color'         => '#F59E0B',
      'source_type'   => 'note',
      'source_id'     => $note->id,
      'metadata'      => [
        'is_pinned'   => $note->is_pinned,
        'is_archived' => $note->is_archived,
      ],
    ];

    if ($existing) {
      $existing->update($eventData);
      return $existing;
    }

    $eventData['code'] = getCode('calendar_event');
    return CalendarEvent::create($eventData);
  }

  public function removeSource(string $sourceType, int $sourceId): void
  {
    CalendarEvent::where('source_type', $sourceType)
      ->where('source_id', $sourceId)
      ->delete();
  }
}
