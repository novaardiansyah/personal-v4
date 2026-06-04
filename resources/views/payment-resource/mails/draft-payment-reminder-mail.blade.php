@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['author_name'])[0] }},
@endsection

@section('content')
  @if($data['draft_count'] > 0)
    <p>Berikut adalah daftar transaksi draft yang dijadwalkan untuk hari ini <strong>{{ $data['date'] }}</strong>. Silakan tinjau dan lakukan tindakan yang diperlukan.</p>

    <div class="card mb-2">
      <div class="group">
        <h4>Ringkasan Draft</h4>
        <ul class="list-flush">
          <li><strong>Jumlah Transaksi</strong>: {{ $data['draft_count'] }} transaksi</li>
          <li><strong>Total Pengeluaran</strong>: {{ toIndonesianCurrency($data['draft_expense']) }}</li>
          <li><strong>Total Pendapatan</strong>: {{ toIndonesianCurrency($data['draft_income']) }}</li>
          <li><strong>Total Lainnya</strong>: {{ toIndonesianCurrency($data['draft_other']) }}</li>
        </ul>
      </div>
    </div>

    <p>Detail lengkap transaksi draft terlampir dalam file PDF.</p>
  @else
    <p>Tidak ada transaksi draft yang ditemukan untuk hari ini <strong>{{ $data['date'] }}</strong>.</p>
  @endif
@endsection
