@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['author_name'])[0] }},
@endsection

@section('content')
  <p>Berikut adalah pengingat tagihan berlangganan Anda yang akan jatuh tempo dalam waktu dekat.</p>

  <div class="card mb-2">
    <div class="group">
      <h4>Detail Berlangganan</h4>
      <ul class="list-flush">
        <li><strong>Nama</strong>: {{ $data['name'] }}</li>
        <li><strong>Nominal</strong>: {{ $data['amount'] }}</li>
        <li><strong>Jatuh Tempo</strong>: {{ $data['next_date'] }}</li>
        <li><strong>Siklus</strong>: {{ ucfirst($data['cycle']) }}</li>
      </ul>
    </div>
  </div>

  <p>Mohon segera lakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari gangguan layanan.</p>
@endsection
