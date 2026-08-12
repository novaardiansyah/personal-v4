@use('App\Models\PaymentType')

@if ($record->has_items && $record->items->isNotEmpty())
  @php
    $itemCount = $record->items->count();
  @endphp

  @foreach ($record->items as $index => $item)
    @php
      $itemTotal = (int) ($item->pivot->total ?? 0);
      $amount    = (int) $record->type_id === PaymentType::TRANSFER || (int) $record->type_id === PaymentType::WITHDRAWAL ? toIndonesianCurrency($itemTotal) : toIndonesianCurrency(0);
      $income    = (int) $record->type_id === PaymentType::INCOME ? toIndonesianCurrency($itemTotal) : toIndonesianCurrency(0);
      $expense   = (int) $record->type_id === PaymentType::EXPENSE ? toIndonesianCurrency($itemTotal) : toIndonesianCurrency(0);
      $itemName  = $item->name . ' (x' . ($item->pivot->quantity ?? 1) . ')';
    @endphp

    <tr>
      @if ($index === 0)
        <th scope="row" style="width: 30px; text-align: center;" rowspan="{{ $itemCount }}">{{ $loopIndex }}</th>
        <td style="width: 110px; white-space: nowrap;" rowspan="{{ $itemCount }}">{{ $record->code }}</td>
        <td style="width: 80px; white-space: nowrap;" rowspan="{{ $itemCount }}">{{ carbonTranslatedFormat($record->date, 'd M Y') }}</td>
        <td style="width: 120px;" rowspan="{{ $itemCount }}">{{ $record->payment_account?->name }}{{ $record->payment_account_to ? ' → ' . $record->payment_account_to->name : '' }}</td>
      @endif
      <td style="width: auto;">{{ $itemName }}</td>
      <td style="width: 105px; text-align: right; white-space: nowrap;">{{ $amount }}</td>
      <td style="width: 105px; text-align: right; white-space: nowrap;">{{ $income }}</td>
      <td style="width: 105px; text-align: right; white-space: nowrap;">{{ $expense }}</td>
    </tr>
  @endforeach
@else
  @php
    $amount  = toIndonesianCurrency($record->amount ?? 0);
    $income  = toIndonesianCurrency($record->income ?? 0);
    $expense = toIndonesianCurrency($record->expense ?? 0);
  @endphp

  <tr>
    <th scope="row" style="width: 30px; text-align: center;">{{ $loopIndex }}</th>
    <td style="width: 110px; white-space: nowrap;">{{ $record->code }}</td>
    <td style="width: 80px; white-space: nowrap;">{{ carbonTranslatedFormat($record->date, 'd M Y') }}</td>
    <td style="width: 120px;">{{ $record->payment_account?->name }}{{ $record->payment_account_to ? ' → ' . $record->payment_account_to->name : '' }}</td>
    <td style="width: auto;">{{ $record->name }}</td>
    <td style="width: 105px; text-align: right; white-space: nowrap;">{{ $amount }}</td>
    <td style="width: 105px; text-align: right; white-space: nowrap;">{{ $income }}</td>
    <td style="width: 105px; text-align: right; white-space: nowrap;">{{ $expense }}</td>
  </tr>
@endif
