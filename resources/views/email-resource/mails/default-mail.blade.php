@extends('layout.mails.default')

@section('title')
  {{ $data['subject'] }}
@endsection


@section('content')
  {!! str($data['message'])->sanitizeHtml() !!}

  @if ($data['url_attachment'] ?? false)
    <p>Unduh lampiran: <a href="{{ $data['url_attachment'] }}">{{ $data['url_attachment'] }}</a></p>
  @endif
@endsection
