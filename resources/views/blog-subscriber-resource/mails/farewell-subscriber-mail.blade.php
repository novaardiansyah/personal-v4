@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Sampai Jumpa, {{ $data['name'] }}!
@endsection

@section('content')
  <p>Sayang sekali Anda memutuskan untuk berhenti berlangganan Nova Blog. Saya akan merindukan kehadiran Anda!</p>

  <p>Jika ada hal yang bisa saya perbaiki atau tingkatkan, jangan ragu untuk memberitahu saya. Masukan Anda sangat berarti.</p>

  <p>Pintu selalu terbuka jika Anda ingin kembali. Cukup klik tombol di bawah ini kapan saja Anda berubah pikiran.</p>

  <p style="text-align: center; margin: 30px 0;">
    <a href="{{ $data['resubscribe_url'] }}" style="background-color: #3366FF; color: #ffffff; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">Berlangganan Lagi</a>
  </p>

  <p>Semoga kita bisa bertemu lagi di lain kesempatan. Sukses selalu untuk Anda!</p>
@endsection
