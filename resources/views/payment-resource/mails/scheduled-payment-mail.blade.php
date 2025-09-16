@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['author_name'])[0] }},
@endsection

@section('content')
  <p>Berikut adalah ringkasan laporan keuangan terjadwal yang telah dibuat dan diproses secara otomatis pada periode
  <strong>{{ $data['date'] }}</strong>.</p>
  
  @php
    $income  = $data['payment']['daily_income'] ?? 0;
    $expense = $data['payment']['daily_expense'] ?? 0;
    $other   = $data['payment']['daily_other'] ?? 0;
  @endphp

  <div class="card mb-2">
    <div class="group">
      <h4>Informasi Keuangan</h4>
      <ul class="list-flush">
        <li>
          <strong>Transfer</strong>: 
          {{ toIndonesianCurrency($other) }} 
          @if ($other > 0)
            ({{ $data['payment']['daily_other_count'] ?? 0 }}x Trx)
          @endif
        </li>
        <li>
          <strong>Pendapatan</strong>: 
          {{ toIndonesianCurrency($income) }} 
          @if ($income > 0)
            ({{ $data['payment']['daily_income_count'] ?? 0 }}x Trx)
          @endif
        </li>
        <li>
          <strong>Pengeluaran</strong>: 
          {{ toIndonesianCurrency($expense) }}
          @if ($expense > 0)
            ({{ $data['payment']['daily_expense_count'] ?? 0 }}x Trx)
          @endif
        </li>
      </ul>
    </div>
  </div>

  <div class="card">
    <div class="group">
      <h4>Informasi Terkait</h4>
      <ul class="list-flush">
        @php
          $total = 0;
        @endphp

        @foreach ($data['payment_accounts'] as $item)
          <li><strong>{{ $item['name'] }}</strong>: {{ toIndonesianCurrency($item['deposit'] ?? 0) }}</li>

          @php
            $total += $item['deposit'] ?? 0;
          @endphp
        @endforeach
        <li><strong>Total: <span class="text-primary">{{ toIndonesianCurrency($total) }}</span></strong></li>
      </ul>
    </div>
  </div>

  <p>Silakan periksa informasi keuangan dan akun terkait di atas untuk memastikan semuanya sesuai dengan yang diharapkan. Jika ada pertanyaan atau klarifikasi, jangan ragu untuk menghubungi kami.</p>
@endsection
