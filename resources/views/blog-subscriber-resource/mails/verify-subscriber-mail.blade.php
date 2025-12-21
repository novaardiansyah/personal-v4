@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ $data['name'] }},
@endsection

@section('content')
  <p>Terima kasih telah berlangganan nova blog. Untuk memastikan bahwa email ini benar milik Anda, silakan klik tombol di bawah untuk memverifikasi langganan Anda.</p>

  <p style="text-align: center; margin: 30px 0;">
    <a href="{{ $data['verify_url'] }}" style="background-color: #3366FF; color: #ffffff; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">Verifikasi Email</a>
  </p>

  <p>Jika tombol di atas tidak berfungsi, Anda juga dapat menyalin dan menempelkan link berikut ke browser Anda:</p>

  <p style="word-break: break-all;"><a href="{{ $data['verify_url'] }}">{{ $data['verify_url'] }}</a></p>

  <p>Jika Anda tidak merasa mendaftar untuk berlangganan blog ini, silakan abaikan email ini.</p>
@endsection
