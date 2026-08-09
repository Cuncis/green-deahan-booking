@php
    $paketList = [
        [
            'key' => 'basic',
            'nama' => 'Basic',
            'tagline' => 'Untuk lapangan tunggal yang baru mulai online.',
            'harga' => 'Rp1,5jt',
            'satuan' => '/tahun, nempel domain kami',
            'featured' => false,
        ],
        [
            'key' => 'pro',
            'nama' => 'Pro',
            'tagline' => 'Untuk lapangan yang serius cari pelanggan dan untung.',
            'harga' => 'Rp2,5jt',
            'satuan' => '/tahun, domain dan hosting sendiri',
            'featured' => true,
        ],
        [
            'key' => 'premium',
            'nama' => 'Premium',
            'tagline' => 'Untuk multi-lapangan / cabang dengan fitur lengkap.',
            'harga' => 'Rp4,5jt',
            'satuan' => '/tahun, full custom dan prioritas',
            'featured' => false,
        ],
    ];

    $sections = [
        'Fitur Inti Booking' => [
            ['Jadwal lapangan real-time', 'yes', 'yes', 'yes'],
            ['Booking mandiri 24 jam', 'yes', 'yes', 'yes'],
            ['Anti double-booking otomatis', 'yes', 'yes', 'yes'],
            ['Konfirmasi otomatis (WA/email)', 'yes', 'yes', 'yes'],
            ['Tampilan mobile-friendly', 'yes', 'yes', 'yes'],
            ['Info lapangan (foto, peta, harga)', 'yes', 'yes', 'yes'],
        ],
        'Pembayaran & Pengelolaan' => [
            ['Pembayaran online (QRIS, VA, e-wallet)', 'yes', 'yes', 'yes'],
            ['Pilihan DP atau bayar penuh', 'no', 'yes', 'yes'],
            ['Dashboard pemilik', 'Dasar', 'yes', 'Lengkap'],
            ['Laporan pendapatan & jam ramai', 'no', 'yes', 'yes'],
            ['Notifikasi tiap ada booking', 'yes', 'yes', 'yes'],
            ['Kelola banyak lapangan', '1 lapangan', '3 lapangan', 'Unlimited'],
        ],
        'Fitur Premium' => [
            ['Membership & pelanggan langganan', 'no', 'no', 'yes'],
            ['Kode promo & diskon', 'no', 'yes', 'yes'],
            ['Booking berulang (jadwal rutin)', 'no', 'yes', 'yes'],
            ['Rating & ulasan pelanggan', 'no', 'yes', 'yes'],
            ['Reminder terjadwal sebelum main (klik kirim WA)', 'no', 'no', 'yes'],
            ['Domain & branding sendiri', 'no', 'yes', 'yes'],
            ['Multi-cabang', 'no', 'no', 'yes'],
        ],
        'Layanan & Support' => [
            ['Setup awal oleh tim kami', 'yes', 'yes', 'yes'],
            ['Support 1 tahun', 'Email', 'WA + Email', 'Prioritas'],
            ['Pelatihan penggunaan', 'no', 'Online', 'Online + Video'],
            ['Update fitur gratis', 'no', 'yes', 'yes'],
        ],
    ];

    $addons = [
        ['Domain custom (.com) per tahun', 'Rp250rb'],
        ['Tambahan lapangan (di luar paket)', 'Rp200rb/lapangan'],
        ['Desain logo & branding', 'Rp500rb'],
        ['Integrasi WhatsApp Business', 'Rp350rb'],
        ['Foto profesional lapangan', 'Rp500rb'],
        ['Setup Google Maps & SEO dasar', 'Rp400rb'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Paket Website Booking Lapangan, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f7f5f2] font-marketing text-stone-900 antialiased">

        <x-site-nav :nav-links="[
            ['label' => 'Beranda', 'href' => '/'],
            ['label' => 'Galeri', 'href' => '/galeri'],
            ['label' => 'Blog', 'href' => '/blog'],
            ['label' => 'Website Booking', 'href' => '/harga'],
            ['label' => 'Kontak', 'href' => '/kontak'],
        ]" />

        <div class="mx-auto max-w-5xl px-5 pb-16 pt-28">
            <div class="mb-10 text-center">
                <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Pilihan Paket</span>
                <h1 class="font-marketing-display mb-3 text-3xl font-black text-stone-900 md:text-4xl">Paket Website Booking Lapangan</h1>
                <p class="mx-auto max-w-xl text-sm text-stone-500">
                    Punya lapangan? Kelola booking-nya lewat website sendiri. Pilih paket sesuai kebutuhan dan skala bisnismu, semua paket sudah termasuk setup dan support 1 tahun.
                </p>
            </div>

            <!-- Pricing cards -->
            <div class="mb-12 grid grid-cols-1 gap-5 md:grid-cols-3">
                @foreach ($paketList as $paket)
                    <div class="relative flex flex-col rounded-2xl border bg-white p-6 {{ $paket['featured'] ? 'border-2 border-brand shadow-xl shadow-brand-dark/10' : 'border-stone-200' }}">
                        @if ($paket['featured'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-brand px-4 py-1 text-[0.65rem] font-extrabold uppercase tracking-wide text-white">Paling Populer</span>
                        @endif

                        <div class="mb-2 text-xs font-bold uppercase tracking-wide text-stone-400">{{ $paket['nama'] }}</div>
                        <p class="mb-4 min-h-[38px] text-xs text-stone-500">{{ $paket['tagline'] }}</p>
                        <div class="font-marketing-display text-3xl font-black text-brand">{{ $paket['harga'] }}</div>
                        <p class="mb-5 text-xs text-stone-400">{{ $paket['satuan'] }}</p>

                        <a
                            href="/daftar?paket={{ $paket['key'] }}"
                            class="mt-auto rounded-xl px-5 py-3 text-center text-sm font-bold transition-colors {{ $paket['featured'] ? 'bg-brand text-white hover:bg-brand-dark' : 'border-2 border-stone-200 text-stone-600 hover:border-brand hover:text-brand' }}"
                        >
                            Pilih {{ $paket['nama'] }}
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- Comparison table -->
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] border-collapse text-sm">
                        <thead>
                            <tr class="bg-brand text-white">
                                <th class="w-2/5 px-6 py-4 text-left text-xs font-bold">Fitur</th>
                                <th class="px-3 py-4 text-center text-xs font-bold">Basic</th>
                                <th class="bg-brand-dark px-3 py-4 text-center text-xs font-bold">
                                    Pro
                                    <span class="mt-0.5 block text-[0.62rem] font-semibold opacity-85">Populer</span>
                                </th>
                                <th class="px-3 py-4 text-center text-xs font-bold">Premium</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sections as $judulSeksi => $baris)
                                <tr class="bg-[#f7f5f2]">
                                    <td colspan="4" class="px-6 py-2.5 text-xs font-bold uppercase tracking-wide text-brand">{{ $judulSeksi }}</td>
                                </tr>
                                @foreach ($baris as [$fitur, $basic, $pro, $premium])
                                    <tr class="border-b border-stone-100 hover:bg-[#fbf8f2]">
                                        <td class="px-6 py-3 font-medium text-stone-600">{{ $fitur }}</td>
                                        <td class="px-3 py-3 text-center">
                                            @if ($basic === 'yes')
                                                <x-icon name="check-circle" size="17" class="mx-auto text-brand" />
                                            @elseif ($basic === 'no')
                                                <span class="text-stone-300">&minus;</span>
                                            @else
                                                <span class="text-xs font-semibold text-brown">{{ $basic }}</span>
                                            @endif
                                        </td>
                                        <td class="bg-brand-50/60 px-3 py-3 text-center">
                                            @if ($pro === 'yes')
                                                <x-icon name="check-circle" size="17" class="mx-auto text-brand" />
                                            @elseif ($pro === 'no')
                                                <span class="text-stone-300">&minus;</span>
                                            @else
                                                <span class="text-xs font-semibold text-brown">{{ $pro }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            @if ($premium === 'yes')
                                                <x-icon name="check-circle" size="17" class="mx-auto text-brand" />
                                            @elseif ($premium === 'no')
                                                <span class="text-stone-300">&minus;</span>
                                            @else
                                                <span class="text-xs font-semibold text-brown">{{ $premium }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8 rounded-r-xl border-l-4 border-brand bg-brand-100 px-6 py-5 text-sm text-stone-800">
                <strong class="text-brand">Keterangan:</strong> tanda centang berarti tersedia, tanda minus berarti tidak termasuk, teks berarti tersedia dengan batasan tertentu.
                Semua harga adalah <strong>estimasi per tahun</strong> dan bisa disesuaikan. Setelah tahun pertama, ada biaya perpanjangan tahunan yang lebih ringan untuk hosting, domain, dan maintenance.
            </div>

            <!-- Add-ons -->
            <div class="mt-6 rounded-2xl border border-dashed border-stone-300 bg-white p-6">
                <h3 class="font-marketing-display mb-4 text-base font-black text-stone-900">Tambahan Opsional (Add-on)</h3>
                <div class="grid grid-cols-1 gap-x-8 gap-y-2 sm:grid-cols-2">
                    @foreach ($addons as [$label, $harga])
                        <div class="flex items-center justify-between border-b border-stone-100 py-1.5 text-sm">
                            <span class="text-stone-600">{{ $label }}</span>
                            <span class="font-bold text-brand">{{ $harga }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-10 text-center text-xs text-stone-400">
                <strong class="text-brand">Green Deahan Sport</strong>, berpengalaman sejak 2010, 0813-5757-0064, greendeahan.com
            </div>
        </div>

        <x-site-footer />
    </body>
</html>
