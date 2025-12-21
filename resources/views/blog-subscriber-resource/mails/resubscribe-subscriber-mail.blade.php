@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Selamat Datang Kembali!
@endsection

@section('content')
  <p>Kami sangat senang Anda kembali! Berlangganan Anda telah berhasil diaktifkan kembali. Anda akan mulai menerima update artikel terbaru lagi.</p>

  <div class="card mb-2" style="width: fit-content; min-width: 280px; padding: 15px 20px;">
    <h5 style="margin-bottom: 8px; font-size: 13px; color: #3366FF;">Email Terdaftar</h5>
    <p style="margin: 0; font-weight: bold; color: #3366FF; font-size: 16px;">{{ $data['email'] }}</p>
  </div>

  <p style="font-size: 14px; color: #777; margin-top: 15px;">
    Diaktifkan kembali pada {{ $data['resubscribed_at_formatted'] }}
  </p>

  <p style="margin-top: 35px; text-align: center;">
    <a href="{{ $data['blog_url'] }}" style="background-color: #3366FF; color: #ffffff; padding: 12px 35px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">Kunjungi Blog</a>
  </p>

  <p>
    Jika Anda ingin berhenti berlangganan lagi, silakan klik <a href="{{ $data['unsubscribe_url'] }}" style="color: #3366FF; text-decoration: underline;">di sini</a>.
  </p>
@endsection
