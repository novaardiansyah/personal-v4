@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['name'])[0] }},
@endsection

@section('content')
  {!! str($data['message'])->sanitizeHtml() !!}

  @if ($data['url_attachment'] ?? false)
    <p>Unduh lampiran: <a href="{{ $data['url_attachment'] }}">{{ $data['url_attachment'] }}</a></p>
  @endif
@endsection
