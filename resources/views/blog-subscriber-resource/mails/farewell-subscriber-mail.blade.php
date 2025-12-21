@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Sampai Jumpa Lagi!
@endsection

@section('content')
  <p>Kami sedih melihat Anda pergi. Email Anda telah berhasil dihapus dari daftar buletin kami. Anda tidak akan lagi menerima update artikel terbaru dari kami.</p>

  <div class="card mb-2" style="width: fit-content; min-width: 280px; padding: 15px 20px;">
    <h5 style="margin-bottom: 8px; font-size: 13px; color: #3366FF;">Email Terdaftar</h5>
    <p style="margin: 0; font-weight: bold; color: #3366FF; font-size: 16px;">{{ $data['email'] }}</p>
  </div>

  <p style="font-size: 14px; color: #777; margin-top: 15px;">
    Berhenti berlangganan pada {{ $data['unsubscribed_at_formatted'] }}
  </p>

  <p style="margin-top: 30px;">
    Pintu selalu terbuka jika Anda ingin kembali. Silakan klik tombol di bawah ini:
  </p>

  <p style="text-align: center; margin: 25px 0;">
    <a href="{{ $data['resubscribe_url'] }}" style="background-color: #3366FF; color: #ffffff; padding: 12px 35px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">Berlangganan Lagi</a>
  </p>
@endsection
