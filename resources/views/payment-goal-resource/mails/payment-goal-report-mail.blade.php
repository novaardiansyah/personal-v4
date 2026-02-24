@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['author_name'])[0] }},
@endsection

@section('content')
  <p>Laporan target pembayaran Anda telah berhasil dibuat dan dilampirkan dalam email ini.</p>

  <p>Silakan periksa lampiran untuk melihat detail laporan selengkapnya.</p>
@endsection
