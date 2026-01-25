@extends('layout.mails.main-light')

@section('title')
  {{ $data['subject'] }}
@endsection

@section('header')
  Hai {{ explode(' ', $data['name'])[0] }},
@endsection

@section('content')
  <p>Terima kasih sudah menghubungi saya. Pesanmu sudah saya terima dan akan saya balas secepatnya, biasanya kurang dari
    24 jam.</p>

  <p>Kalau ada pertanyaan lain atau ingin membahas hal lain, silakan hubungi saya lagi kapan saja.</p>

  <p>Sekali lagi, terima kasih atas ketertarikanmu. Semoga kita bisa segera ngobrol atau bekerja sama.</p>

  <p>Agar lebih cepat, kamu juga bisa langsung menghubungi saya lewat <a
      href="https://wa.me/6282261111084?text=Hai+Nova%2C+saya+ingin+terhubung+dengan+Anda+segera%2C+mohon+balas+secepatnya%21"
      target="_blank">WhatsApp</a>.</p>
@endsection
