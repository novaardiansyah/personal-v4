@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Selamat Datang, {{ $data['name'] }}!
@endsection

@section('content')
  <p>Email Anda telah berhasil diverifikasi. Sekarang Anda resmi menjadi subscriber Nova Blog.</p>

  <p>Anda akan menerima notifikasi setiap kali ada artikel baru yang dipublikasikan. Jangan khawatir, saya tidak akan mengirimkan spam.</p>

  <p style="text-align: center; margin: 30px 0;">
    <a href="{{ $data['blog_url'] }}" style="background-color: #3366FF; color: #ffffff; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">Kunjungi Blog</a>
  </p>

  <p>Jika suatu saat Anda ingin berhenti berlangganan, klik link di bawah ini:</p>

  <p style="word-break: break-all;"><a href="{{ $data['unsubscribe_url'] }}">{{ $data['unsubscribe_url'] }}</a></p>
@endsection
