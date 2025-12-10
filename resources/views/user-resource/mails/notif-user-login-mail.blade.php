@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['author_name'])[0] }},
@endsection

@section('content')
  <p>Kami ingin menginformasikan bahwa ada pengguna yang baru saja login ke {{ ($data['guard'] ?? null) === 'api' ? 'sistem melalui API' : 'situs web' }} Anda. Berikut adalah detail login pengguna tersebut:</p>

  <div class="card" style="margin-top: 20px;">
    <div class="group">
      <h4>Detail Pengguna</h4>
      <ul class="list-flush">
        <li>
          <strong>Alamat Email</strong>: {{ $data['email_user'] ? textLower($data['email_user']) : '-' }}
        </li>
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
          <strong>Waktu Login</strong>: {{ $data['created_at'] ? carbonTranslatedFormat($data['created_at'], 'd F Y H:i', 'id') . ' WIB' : '-' }}
        </li>
        <li>
          <strong>Referer</strong>: <a href="{{ $data['referer'] ?? '#' }}" target="_blank">{{ $data['referer'] ?? '-' }}</a>
        </li>
      </ul>
    </div>
  </div>

  <p>Jika ini bukan aktivitas yang Anda kenali, sebagai tindakan pencegahan, kami sarankan Anda untuk segera memeriksa aktivitas pengguna tersebut dan melakukan tindakan yang diperlukan.</p>
@endsection
