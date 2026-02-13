@php
  $author_name = getSetting('author_name');
@endphp

Kami ingin menginformasikan bahwa ada pengguna yang baru saja login ke sistem Anda.

*Detail Pengguna:*

• Alamat Email : {{ $user_email }}
• Alamat IP : {{ $ip_address ?? '-' }}
• Lokasi : {{ $address ?? '-' }}
• Geolokasi : {{ $geolocation ?? '-' }}
• Zona Waktu : {{ $timezone ?? '-' }}
• Waktu Login : {{ $login_date }} WIB
• Referer : {{ $referer }}
• Perangkat : {{ $user_agent }}

Jika ini bukan aktivitas yang Anda kenali, sebagai tindakan pencegahan, kami sarankan Anda untuk segera memeriksa aktivitas pengguna tersebut dan melakukan tindakan yang diperlukan.

Terima kasih atas perhatian Anda.

Salam hangat,

{{ explode(' ', $author_name)[0] }}
