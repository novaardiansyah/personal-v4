@php
  $amount = toIndonesianCurrency($record->amount ?? 0);
  $income = toIndonesianCurrency($record->income ?? 0);
  $expense = toIndonesianCurrency($record->expense ?? 0);
@endphp

<tr>
  <th scope="row" width="35px">{{ $loopIndex }}</th>
  <td>{{ $record->code }}</td>
  <td>{{ carbonTranslatedFormat($record->date, 'd M Y') }}</td>
  <td>{{ $record->payment_account->name }}</td>
  <td>{{ $record->name }}</td>
  <td style="text-align: right;">{{ $amount }}</td>
  <td style="text-align: right;">{{ $income }}</td>
  <td style="text-align: right;">{{ $expense }}</td>
</tr>
