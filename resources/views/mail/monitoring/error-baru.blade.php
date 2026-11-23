<x-mail::message>
# {{ ! empty($info['uji']) ? 'Email uji berhasil' : 'Ada error baru di aplikasi' }}

{{ $info['pesan'] }}

- Level: **{{ strtoupper($info['level']) }}**
@if (! empty($info['kelas']))
- Jenis: `{{ $info['kelas'] }}`
@endif
@if (! empty($info['lokasi']))
- Lokasi: `{{ $info['lokasi'] }}`
@endif
- Waktu: {{ $info['waktu'] }} WIB
- Lingkungan: {{ $info['lingkungan'] }}

<x-mail::button :url="$urlLog">
Buka Log Error
</x-mail::button>

@if (empty($info['uji']))
Error yang sama tidak dikabarkan lagi selama satu jam.
@endif
</x-mail::message>
