<tr>
  <td>{{ $loopIndex }}</td>
  <td>{{ $record->code }}</td>
  <td>{{ $record->name }}</td>
  <td>{{ $record->status->name ?? '-' }}</td>
  <td>{{ toIndonesianCurrency($record->target_amount) }}</td>
  <td>{{ toIndonesianCurrency($record->amount) }}</td>
  <td>{{ $record->latest_progress_percent }}%</td>
  <td>{{ \Carbon\Carbon::parse($record->start_date)->format('d M Y') }}</td>
  <td>{{ \Carbon\Carbon::parse($record->target_date)->format('d M Y') }}</td>
</tr>
