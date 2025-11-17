@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['author_name'])[0] }},
@endsection

@section('content')
  <p>Kami ingin menginformasikan bahwa ada pesan baru yang masuk melalui formulir kontak pada situs web Anda.</p>

  <div class="card">
    <div class="group">
      <h4>Detail Pesan</h4>
      <ul class="list-flush">
        <li>
          <strong>Nama</strong>: {{ $data['name'] ?? '-' }}
        </li>
        <li>
          <strong>Email</strong>: {{ $data['email_contact'] ?? '-' }}
        </li>
        <li>
          <strong>Subjek</strong>: {{ $data['subject_contact'] ?? '-' }}
        </li>
        <li>
          <strong>Pesan</strong>: {{ $data['message'] ?? '-' }}
        </li>
      </ul>
    </div>
  </div>

  <div class="card" style="margin-top: 20px;">
    <div class="group">
      <h4>Detail Pengirim</h4>
      <ul class="list-flush">
        <li>
          <strong>Alamat IP</strong>: {{ $data['ip_address'] ?? '-' }}
        </li>
        <li>
          <strong>Lokasi</strong>: {{ $data['address'] ?? '-' }}
        </li>
        <li>
          <strong>Geolokasi</strong>: {{ $data['geolocation'] ?? '-' }}
        </li>
        <li>
          <strong>Zona Waktu</strong>: {{ $data['timezone'] ?? '-' }}
        </li>
        <li>
          <strong>Perangkat</strong>: {{ $data['user_agent'] ?? '-' }}
        </li>
        <li>
          <strong>Waktu Pengiriman</strong>: {{ $data['created_at'] ? carbonTranslatedFormat($data['created_at'], 'd F Y H:i') : '-' }}
        </li>
        <li>
          <strong>Referer</strong>: <a href="{{ $data['url'] ?? '#' }}" target="_blank">{{ $data['url'] ?? '-' }}</a>
        </li>
      </ul>
    </div>
  </div>
@endsection