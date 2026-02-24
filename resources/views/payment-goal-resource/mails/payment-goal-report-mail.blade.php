@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['author_name'])[0] }},
@endsection

@section('content')
  <p>Berikut adalah laporan target pembayaran Anda.</p>

  <p>Laporan telah tersedia dan dapat diunduh melalui tautan di bawah ini:</p>

  <div style="text-align: center; margin: 30px 0;">
    <a href="{{ $data['download_url'] ?? '#' }}" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; background-color: #155dfc; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
      Unduh Laporan
    </a>
  </div>

  <p style="font-size: 12px; color: #666;">
    Tautan unduhan akan berlaku selama 1 bulan.
  </p>
@endsection
