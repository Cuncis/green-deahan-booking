<?php

namespace Database\Seeders;

use App\Models\CatatanPromosi;
use Illuminate\Database\Seeder;

/**
 * Catatan awal untuk menu Superadmin > Catatan Promosi: rencana kampanye Google
 * Ads promo NMAX, prompt gambar popup banner, dan kode tombol WhatsApp.
 *
 * Aman dijalankan ulang dan di production: tiap catatan dicari lewat judulnya
 * (dibuat kalau belum ada, tidak menimpa catatan yang sudah Anda ubah).
 *
 *   php artisan db:seed --class=CatatanPromosiSeeder --force
 */
class CatatanPromosiSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catatan() as $catatan) {
            $dibuat = CatatanPromosi::firstOrCreate(['judul' => $catatan['judul']], $catatan);

            $this->command?->line(($dibuat->wasRecentlyCreated ? 'Dibuat   ' : 'Dilewati ').$catatan['judul']);
        }

        $this->perbaruiNomorWhatsApp();
        $this->perbaruiPromptSudut();
        $this->pindahkanMetaAds();
    }

    /**
     * Catatan Meta Ads hasil seeder lama ada di kategori "Rencana kampanye"; sekarang punya tab sendiri.
     */
    private function pindahkanMetaAds(): void
    {
        CatatanPromosi::query()->where('kategori', 'kampanye')->where('judul', 'like', 'Meta Ads%')->update(['kategori' => 'meta-ads']);
    }

    /**
     * Prompt popup versi lama meminta "sudut membulat" di dalam gambar, yang membuat sudut popup
     * tampak tidak rata. Hanya frasa itu yang diganti pada catatan hasil seeder lama.
     */
    private function perbaruiPromptSudut(): void
    {
        $pengganti = [
            'dengan sudut membulat halus, gaya foto' => 'gambar penuh sampai ke tepi dengan sudut kotak tajam (tanpa sudut membulat, tanpa bingkai, tanpa margin putih), gaya foto',
            'with softly rounded corners, minimalist' => 'full-bleed artwork edge to edge with sharp square corners (no rounded corners, no border, no frame, no white margin), minimalist',
        ];

        CatatanPromosi::query()
            ->where(fn ($query) => $query->where('isi', 'like', '%sudut membulat halus%')->orWhere('isi', 'like', '%softly rounded corners%'))
            ->each(fn (CatatanPromosi $catatan) => $catatan->update(['isi' => strtr($catatan->isi, $pengganti)]));
    }

    /**
     * Nomor WhatsApp kampanye berganti dari 081357570063 ke 0821357570064. Catatan hasil
     * seeder versi lama hanya diganti nomornya, bagian lain yang sudah Anda ubah tidak disentuh.
     */
    private function perbaruiNomorWhatsApp(): void
    {
        $pengganti = ['081357570063' => '0821357570064', '6281357570063' => '62821357570064'];

        CatatanPromosi::query()
            ->where(fn ($query) => $query->where('isi', 'like', '%081357570063%')->orWhere('isi', 'like', '%6281357570063%'))
            ->each(fn (CatatanPromosi $catatan) => $catatan->update(['isi' => strtr($catatan->isi, $pengganti)]));
    }

    /**
     * @return array<int, array{judul: string, kategori: string, isi: string, disematkan: bool}>
     */
    private function catatan(): array
    {
        return [
            [
                'judul' => 'Google Ads Search: Promo NMAX Kontraktor Futsal 2026',
                'kategori' => 'google-ads',
                'disematkan' => true,
                'isi' => <<<'TEXT'
STRUKTUR KAMPANYE
Kampanye: Promo NMAX Kontraktor Futsal 2026
Tujuan: Prospek (leads), konversi berupa klik tombol WhatsApp
Lokasi: Kota atau provinsi yang bisa dijangkau tim proyek (jangan seluruh Indonesia kalau belum siap melayani)

Batas karakter Google: judul maks. 30, deskripsi maks. 90. Tanpa em dash.

GRUP IKLAN DAN KATA KUNCI

1. Kontraktor Futsal (grup utama)
- "kontraktor lapangan futsal"
- "jasa pembuatan lapangan futsal"
- "pembuatan lapangan futsal"
- "biaya pembuatan lapangan futsal"
- "harga pembuatan lapangan futsal"
- "bangun lapangan futsal"

2. Rumput Sintetis
- "pemasangan rumput sintetis futsal"
- "harga rumput sintetis lapangan futsal"
- "kontraktor rumput sintetis"

3. Lapangan Olahraga Umum (pakai hanya jika memang membangun jenis lapangan ini)
- "kontraktor lapangan olahraga"
- "jasa pembuatan lapangan padel"
- "kontraktor lapangan mini soccer"

4. Brand
- [green deahan]
- [green deahan sport]

Tanda kutip = phrase match. Kurung siku = exact match. Keduanya lebih hemat budget dibanding broad match untuk kampanye baru.

KATA KUNCI NEGATIF (AWAL)
sewa, booking, jadwal, lowongan, loker, gaji, kerja, game, skor, live, sepatu, bola, jersey, ukuran

"sewa", "booking", dan "jadwal" penting dikecualikan: orang yang mencari sewa lapangan futsal ingin bermain, bukan membangun. Setelah 1 sampai 2 minggu, cek laporan Search Terms di Google Ads lalu tambah atau hapus kata negatif sesuai data asli.

JUDUL IKLAN (Responsive Search Ad)
- Kontraktor Lapangan Futsal (26)
- Green Deahan Sport (18)
- Promo Akhir Tahun 2026 (22)
- Hadiah Langsung Motor NMAX (26)
- Bangun 1500 m2 Dapat NMAX (25)
- Jasa Pembuatan Lapangan (23)
- Konsultasi Gratis via WA (24)
- Rumput Sintetis Berkualitas (27)
- Lapangan Olahraga Profesional (29)
- Berlaku s.d. 31 Desember 2026 (29)
- Klaim Promonya Sekarang (23)
- Lihat Portofolio Proyek Kami (28)
- Bangun Lapangan Futsal Impian (29)

Sematkan (pin) "Kontraktor Lapangan Futsal" atau "Green Deahan Sport" di posisi 1 supaya iklan selalu menyebut nama bisnis.

DESKRIPSI IKLAN
- Promo akhir tahun 2026! Setiap pembangunan lapangan 1500 m2 dapat hadiah motor NMAX. (84)
- Kontraktor lapangan futsal profesional. Konsultasi gratis via WhatsApp sekarang juga. (85)
- Lapangan rapi, rumput sintetis berkualitas, dan pengerjaan tepat waktu oleh tim ahli. (85)
- Promo terbatas hingga 31 Desember 2026. Klaim hadiah NMAX Anda, hubungi kami hari ini. (86)

ASET TAMBAHAN
Sitelink: Promo Hadiah NMAX, Galeri Proyek, Konsultasi via WhatsApp, Tentang Kami
Callout (maks. 25 karakter): Konsultasi Gratis, Hadiah Motor NMAX, Tim Berpengalaman, Promo s.d. Desember 2026
Aset telepon/pesan: 0821357570064
Link sitelink WhatsApp:
https://wa.me/62821357570064?text=Halo%20Green%20Deahan%20Sport%2C%20saya%20tertarik%20dengan%20Promo%20Akhir%20Tahun%202026%20hadiah%20langsung%20motor%20NMAX%20untuk%20pembangunan%20lapangan%20futsal%201500%20m%C2%B2.%20Boleh%20minta%20info%20lebih%20lanjut%3F

HAL PENTING SEBELUM TAYANG
1. Pakai "m2", bukan "m²". Google kadang menolak simbol tidak standar di teks iklan.
2. Merek NMAX: NMAX adalah merek dagang Yamaha. Google bisa membatasi iklan yang memakai merek orang lain bila pemiliknya mengajukan keberatan. Kalau iklan ditolak, ganti dengan "Hadiah Motor Matic Premium".
3. Halaman tujuan: arahkan ke halaman khusus promo, bukan beranda yang memunculkan popup. Popup yang menutupi konten bisa menurunkan skor landing page experience dan menaikkan biaya per klik.
4. Lacak klik WhatsApp sebagai konversi (lewat Google Tag Manager) supaya Google mengoptimalkan iklan untuk orang yang benar-benar menghubungi.
5. Cek klaim di iklan: tanggal berakhir promo dan klaim seperti "tepat waktu" atau "tim ahli" harus sesuai kenyataan, karena iklan yang menyesatkan bisa ditolak.
TEXT,
            ],
            [
                'judul' => 'Prompt gambar popup: Promo Akhir Tahun 2026 (hadiah NMAX)',
                'kategori' => 'prompt-gambar',
                'disematkan' => false,
                'isi' => <<<'TEXT'
PALET WARNA (situs korporat, token di brand-greendeahan.css)
- Hijau #006400: blok warna utama dan judul
- Hijau tua #004d00: badge "HADIAH LANGSUNG"
- Oranye #feb046: hanya untuk tombol WhatsApp (oranye khusus aksi utama dan tombol WhatsApp)
- Teks #1a1a1a: teks utama
- Off-white #F5F5F5: latar area teks, bukan putih murni
Teks batas waktu memakai hijau tua tebal, karena merah tidak ada di palet situs korporat.

SUDUT GAMBAR: jangan minta sudut membulat di dalam gambar. Popup sudah membulatkan sudutnya sendiri (20px). Kalau gambar membawa sudut membulat atau margin putih sendiri, sudut popup tampak tidak rata, terutama di sisi kanan. Buat gambar penuh sampai ke tepi dengan sudut kotak tajam.

CATATAN: tombol WhatsApp bisa diatur dari Superadmin > Banner Promo (tautan dan tombol), jadi tidak perlu digambar di dalam gambar. Kalau memakai tombol dari Banner Promo, hapus bagian tombol dari prompt supaya tidak muncul dua tombol.

PROMPT (bahasa Indonesia)
Desain popup promosi vertikal (rasio 4:5, 1080x1350 piksel), gambar penuh sampai ke tepi dengan sudut kotak tajam (tanpa sudut membulat, tanpa bingkai, tanpa margin putih), gaya foto komersial asli yang minimalis, bersih, dan profesional untuk perusahaan kontraktor lapangan futsal. Bagian atas: blok warna solid hijau tua (#006400), tanpa gradasi. Di paling atas tertulis nama perusahaan "GREEN DEAHAN SPORT" dengan huruf kapital sans-serif yang rapi, ukuran sedang, warna off-white (#F5F5F5), jarak antar huruf lebar agar terlihat elegan. Di bawahnya judul besar "PROMO AKHIR TAHUN 2026" warna off-white. Bagian tengah: foto realistis lapangan futsal indoor yang baru selesai dibangun, permukaan rumput sintetis hijau rapi dengan garis putih tegas, gawang futsal putih di ujung lapangan, atap rangka baja dan dinding jaring, pencahayaan lampu lapangan yang natural. Di tengah lapangan terparkir motor Yamaha NMAX warna hitam doff berukuran cukup besar dan terlihat jelas, dengan bayangan alami, seperti foto dokumentasi proyek asli. Di dekat motor ada badge kecil berbentuk pil warna hijau tua (#004d00) bertuliskan "HADIAH LANGSUNG" warna off-white. Bagian bawah: blok warna solid off-white (#F5F5F5). Teks paling menonjol "Hadiah Langsung Motor NMAX" berukuran besar dan tebal warna hitam (#1a1a1a), di bawahnya "Setiap Pembangunan Lapangan 1500 m²" warna hitam, lalu teks kecil tebal "Hanya sampai 31 Desember 2026" warna hijau tua (#004d00). Paling bawah, tombol lebar hampir selebar desain dengan sudut membulat, warna oranye lembut (#feb046) bertuliskan "Klaim Promo via WhatsApp" warna hitam (#1a1a1a). Tipografi sans-serif modern, tegas, dan mudah dibaca di layar HP. Palet warna terbatas, tata letak bersih dengan ruang kosong yang cukup. Tanpa gradasi, tanpa efek glow, tanpa kembang api, tanpa warna tambahan di luar palet, tanpa sudut membulat atau bingkai. Terlihat seperti foto proyek asli dan iklan profesional, bukan gambar AI.

PROMPT (English, lebih akurat untuk AI gambar)
Vertical promotional popup design (4:5 ratio, 1080x1350), full-bleed artwork edge to edge with sharp square corners (no rounded corners, no border, no frame, no white margin), minimalist, clean and professional style for a futsal court construction company, authentic commercial photography. Top section: solid deep green block (#006400), no gradient. At the very top, the company name "GREEN DEAHAN SPORT" in clean uppercase sans-serif, medium size, off-white (#F5F5F5), wide letter spacing for an elegant look. Below it, large headline "PROMO AKHIR TAHUN 2026" in off-white. Middle section: realistic photo of a newly built indoor futsal court, neat green synthetic turf with crisp white lines, white futsal goal at the far end, steel truss roof and net walls, natural court lighting. A matte black Yamaha NMAX scooter parked at center court, large and clearly visible, with realistic soft shadow, looks like a genuine project documentation photo. Near the scooter, a small pill-shaped dark green (#004d00) badge reading "HADIAH LANGSUNG" in off-white. Bottom section: solid off-white block (#F5F5F5). Most prominent text "Hadiah Langsung Motor NMAX" large and bold in near-black (#1a1a1a), below it "Setiap Pembangunan Lapangan 1500 m²" in near-black, then small bold text "Hanya sampai 31 Desember 2026" in dark green (#004d00). At the very bottom, a wide almost full-width rounded button in soft orange (#feb046) with near-black text "Klaim Promo via WhatsApp". Bold modern sans-serif typography, highly legible on mobile. Limited palette, clean layout, generous spacing. No gradients, no glow, no fireworks, no colors outside the palette. Shot on Sony A7 IV, 24mm lens, true-to-life colors, looks like a real project photo and professional ad, not AI-generated.

NEGATIVE PROMPT
rounded corners, border, frame, white margin, drop shadow, gradient, neon, glow, sparkle, fireworks, confetti, oversaturated, HDR, cartoon, 3D render, plastic look, fantasy, cluttered layout, small text, distorted text, deformed vehicle, extra wheels, wrong court lines, soccer stadium, outdoor grass field, crowd, pure white background, red, purple
TEXT,
            ],
            [
                'judul' => 'Kode tombol WhatsApp untuk popup promo NMAX',
                'kategori' => 'kampanye',
                'disematkan' => false,
                'isi' => <<<'TEXT'
Tombol memakai gaya situs korporat: oranye #feb046, teks #1a1a1a, sudut 12px (kontrol). Class btn-cta sudah mengatur warna dan hover dari token di brand-greendeahan.css, jadi atribut style bisa dihapus supaya mengikuti token.

<a href="https://wa.me/62821357570064?text=Halo%20Green%20Deahan%20Sport%2C%20saya%20tertarik%20dengan%20Promo%20Akhir%20Tahun%202026%20hadiah%20langsung%20motor%20NMAX%20untuk%20pembangunan%20lapangan%20futsal%201500%20m%C2%B2.%20Boleh%20minta%20info%20lebih%20lanjut%3F"
   target="_blank" rel="noopener"
   class="btn-cta"
   style="display:block; width:100%; padding:14px; background:#feb046; color:#1a1a1a; text-align:center; border-radius:12px; font-weight:600; text-decoration:none;">
  Klaim Promo via WhatsApp
</a>

CARA PAKAI DI POPUP BANNER
Di Superadmin > Banner Promo, isi kolom Tautan dengan alamat wa.me di atas, centang "Tampilkan tombol di bawah gambar", dan isi teks tombol "Klaim Promo via WhatsApp". Tidak perlu menggambar tombol di dalam gambar.
TEXT,
            ],
            [
                'judul' => 'Meta Ads (Instagram dan Facebook): Promo Akhir Tahun 2026',
                'kategori' => 'meta-ads',
                'disematkan' => false,
                'isi' => <<<'TEXT'
Gaya tulisan lebih santai dibanding Google Ads: orang di Instagram dan Facebook sedang scrolling, bukan sedang mencari jasa.

STRUKTUR KAMPANYE
Tujuan: Engagement, dengan tujuan percakapan WhatsApp (Click-to-WhatsApp). Pengunjung menekan iklan lalu langsung masuk ke chat WhatsApp. Untuk jasa bernilai besar seperti pembangunan lapangan, cara ini biasanya lebih efektif daripada mengarahkan ke website.
Syarat: nomor 0821357570064 harus terhubung ke Facebook Page Green Deahan Sport lewat WhatsApp Business.
Penempatan: Instagram Feed, Instagram Stories dan Reels, Facebook Feed, Facebook Stories dan Reels.
Budget awal: Rp50.000 sampai Rp100.000 per hari selama 7 hari untuk tes, lalu naikkan budget di iklan yang paling banyak menghasilkan chat.

TARGET AUDIENS
Lokasi: kota atau provinsi yang bisa dijangkau tim proyek.
Usia: 28 sampai 55 tahun (kelompok yang umumnya punya modal untuk membangun lapangan).

Audiens 1: Calon pebisnis lapangan
- Minat: Futsal, Kewirausahaan, Usaha kecil, Investasi, Investasi properti
- Perilaku: Admin Halaman Bisnis Facebook (Facebook Page admins)

Audiens 2: Pemilik lahan dan properti
- Minat: Real estat, Tanah, Pengembang properti, Konstruksi

Audiens 3: Retargeting
- Orang yang pernah mengunjungi greendeahan.com (butuh Meta Pixel terpasang) atau berinteraksi dengan Instagram dan Facebook dalam 90 hari terakhir. Biasanya paling murah dan paling cepat menghasilkan chat.

TEKS UTAMA 1 (fokus hadiah)
Mau bangun lapangan futsal sendiri? Ini waktu yang tepat.

Promo Akhir Tahun 2026 dari Green Deahan Sport: setiap pembangunan lapangan 1500 m², Anda langsung dapat hadiah motor Yamaha NMAX.

Promo hanya sampai 31 Desember 2026. Klik tombol di bawah untuk konsultasi gratis via WhatsApp.

TEKS UTAMA 2 (fokus peluang bisnis)
Lapangan futsal bisa jadi peluang bisnis yang menarik, apalagi kalau dibangun dengan rapi dan berkualitas.

Green Deahan Sport siap membangun lapangan futsal Anda. Bonusnya, khusus akhir tahun ini, pembangunan lapangan 1500 m² dapat hadiah langsung motor NMAX.

Konsultasi gratis, tanpa komitmen. Chat kami sekarang.

TEKS UTAMA 3 (singkat, untuk Stories dan Reels)
Bangun lapangan futsal 1500 m², bawa pulang motor NMAX. Promo akhir tahun Green Deahan Sport, hanya sampai 31 Desember 2026.

JUDUL (maks. 40 karakter)
- Bangun Lapangan, Dapat Motor NMAX (33)
- Promo Akhir Tahun Green Deahan Sport (36)
- Konsultasi Gratis via WhatsApp (30)

DESKRIPSI (maks. 30 karakter)
Berlaku s.d. 31 Desember 2026 (29)

TOMBOL CTA
Kirim Pesan WhatsApp

PESAN PEMBUKA OTOMATIS DI WHATSAPP (diatur di Ads Manager)
Halo Green Deahan Sport, saya tertarik dengan Promo Akhir Tahun 2026 hadiah motor NMAX. Boleh minta info lebih lanjut?

MATERI VISUAL
1. Feed 4:5 (1080x1350): pakai gambar popup yang sudah dibuat, tanpa tombol di dalam gambar karena Meta sudah menampilkan tombol "Kirim Pesan WhatsApp" sendiri.
2. Stories dan Reels 9:16 (1080x1920): pakai catatan "Prompt gambar Stories dan Reels", sisakan area kosong sekitar 250 piksel di bagian atas dan bawah.

TIPS SUPAYA IKLAN META LEBIH BERHASIL
1. Pakai video proyek asli untuk Reels: video 15 sampai 30 detik proses pembangunan (dari tanah kosong sampai jadi), ditutup dengan tampilan motor NMAX, biasanya jauh lebih menarik dan terpercaya daripada gambar diam.
2. Buat iklan carousel portofolio: 4 sampai 6 foto lapangan yang pernah dikerjakan, slide terakhir berisi info promo NMAX.
3. Uji beberapa versi: jalankan 2 sampai 3 kombinasi teks dan gambar sekaligus, matikan yang hasilnya paling buruk setelah 3 sampai 5 hari.
4. Pasang Meta Pixel di greendeahan.com untuk audiens retargeting dan mengukur hasil iklan.
5. Siapkan tim membalas chat dengan cepat. Iklan WhatsApp sia-sia kalau chat baru dibalas berjam-jam kemudian. Siapkan juga template jawaban berisi info promo, syarat, dan pertanyaan lokasi proyek.

SEBELUM TAYANG
- Pastikan semua klaim dan syarat promo (luas minimal, batas waktu, ketentuan hadiah) sesuai kebijakan perusahaan, karena calon klien memakainya sebagai acuan saat menghubungi tim sales.
- Tekan link WhatsApp dari HP untuk memastikan chat terbuka ke nomor 0821357570064.
TEXT,
            ],
            [
                'judul' => 'Prompt gambar Stories dan Reels (9:16): Promo Akhir Tahun 2026',
                'kategori' => 'prompt-gambar',
                'disematkan' => false,
                'isi' => <<<'TEXT'
Ukuran 1080x1920. Sisakan area kosong sekitar 250 piksel di bagian paling atas dan paling bawah tanpa teks penting (area itu tertutup tampilan Stories dan Reels). Palet sama dengan prompt popup: hijau #006400, hijau tua #004d00, off-white #F5F5F5, teks #1a1a1a.

PROMPT (bahasa Indonesia)
Desain iklan vertikal Instagram Story (rasio 9:16, 1080x1920 piksel), gaya foto komersial asli yang minimalis dan profesional untuk kontraktor lapangan futsal. Sisakan area kosong sekitar 250 piksel di bagian paling atas dan paling bawah tanpa teks penting. Bagian atas: blok warna solid hijau tua (#006400) tanpa gradasi, berisi nama "GREEN DEAHAN SPORT" huruf kapital kecil berjarak lebar warna off-white (#F5F5F5) dan judul besar "PROMO AKHIR TAHUN 2026" warna off-white. Bagian tengah yang dominan: foto realistis lapangan futsal indoor baru dengan rumput sintetis hijau, garis putih tegas, gawang putih, atap rangka baja, pencahayaan natural, dan motor Yamaha NMAX hitam doff berukuran besar terparkir di tengah lapangan dengan bayangan alami. Badge kecil berbentuk pil warna hijau tua (#004d00) bertuliskan "HADIAH LANGSUNG". Bagian bawah: blok off-white (#F5F5F5) dengan teks tebal besar "Hadiah Langsung Motor NMAX" warna hitam (#1a1a1a), teks "Setiap Pembangunan Lapangan 1500 m²", dan teks kecil "Hanya sampai 31 Desember 2026" warna hijau tua. Tipografi sans-serif modern yang mudah dibaca di HP. Tanpa gradasi, tanpa glow, tanpa kembang api. Terlihat seperti foto proyek asli dan iklan profesional, bukan gambar AI.

PROMPT (English)
Vertical Instagram Story ad (9:16, 1080x1920), minimalist professional authentic commercial photography for a futsal court contractor. Keep the top and bottom 250 pixels free of important text. Top: solid deep green block (#006400), no gradient, with small uppercase widely spaced "GREEN DEAHAN SPORT" in off-white (#F5F5F5) and large headline "PROMO AKHIR TAHUN 2026" in off-white. Dominant middle: realistic photo of a newly built indoor futsal court, green synthetic turf, crisp white lines, white goal, steel truss roof, natural lighting, with a large matte black Yamaha NMAX scooter parked at center court with realistic shadow. Small pill-shaped dark green (#004d00) badge reading "HADIAH LANGSUNG". Bottom: off-white (#F5F5F5) block with large bold "Hadiah Langsung Motor NMAX" in near-black (#1a1a1a), "Setiap Pembangunan Lapangan 1500 m²", and small "Hanya sampai 31 Desember 2026" in dark green. Modern sans-serif, highly legible on mobile. No gradients, no glow, no fireworks. Shot on Sony A7 IV, 24mm lens, true-to-life colors, looks like a real project photo, not AI-generated.

NEGATIVE PROMPT (sama dengan prompt popup)
rounded corners, border, frame, white margin, drop shadow, gradient, neon, glow, sparkle, fireworks, confetti, oversaturated, HDR, cartoon, 3D render, plastic look, fantasy, cluttered layout, small text, distorted text, deformed vehicle, extra wheels, wrong court lines, soccer stadium, outdoor grass field, crowd, pure white background, red, purple
TEXT,
            ],
            [
                'judul' => 'Google Ads: Kontraktor Lapangan Olahraga (umum, tanpa promo)',
                'kategori' => 'google-ads',
                'disematkan' => false,
                'isi' => <<<'TEXT'
Versi umum tanpa promo NMAX, untuk orang yang ingin MEMBANGUN lapangan. Kampanye promo NMAX bisa berjalan terpisah sampai 31 Desember 2026. Jenis lapangan mengikuti konteks bisnis: futsal, padel, badminton, tenis, mini soccer. Hapus jenis yang tidak dikerjakan.

STRUKTUR KAMPANYE
Tujuan: Prospek, konversi berupa klik WhatsApp
Lokasi: wilayah yang bisa dijangkau tim proyek

GRUP IKLAN DAN KATA KUNCI
- Futsal: "kontraktor lapangan futsal", "jasa pembuatan lapangan futsal", "biaya pembuatan lapangan futsal"
- Padel: "kontraktor lapangan padel", "jasa pembuatan lapangan padel", "biaya bangun lapangan padel"
- Mini Soccer: "kontraktor lapangan mini soccer", "pembuatan lapangan mini soccer"
- Badminton dan Tenis: "kontraktor lapangan badminton", "pembuatan lapangan tenis"
- Rumput Sintetis: "pemasangan rumput sintetis lapangan", "kontraktor rumput sintetis"
- Umum: "kontraktor lapangan olahraga", "jasa pembuatan lapangan olahraga"
- Brand: [green deahan], [green deahan sport]

KATA KUNCI NEGATIF
sewa, booking, jadwal, terdekat, lowongan, loker, gaji, kerja, game, skor, live, sepatu, raket, jersey

JUDUL IKLAN
- Kontraktor Lapangan Olahraga (28)
- Green Deahan Sport (18)
- Kontraktor Lapangan Futsal (26)
- Jasa Pembuatan Lapangan Padel (29)
- Bangun Lapangan Mini Soccer (27)
- Lapangan Badminton & Tenis (26)
- Konsultasi Gratis via WA (24)
- Rumput Sintetis Berkualitas (27)
- Lihat Portofolio Proyek Kami (28)
- Bangun Lapangan Impian Anda (27)
- Tanya Biaya Pembuatan (21)
- Lapangan Siap Pakai & Rapi (26)

DESKRIPSI IKLAN
- Pembuatan lapangan futsal, padel, mini soccer, badminton, dan tenis. Konsultasi gratis. (87)
- Kontraktor lapangan olahraga profesional. Hubungi kami via WhatsApp untuk tanya biaya. (86)
- Lapangan rapi, rumput sintetis berkualitas, dan pengerjaan tepat waktu oleh tim ahli. (85)
- Lihat portofolio proyek kami di greendeahan.com dan wujudkan lapangan olahraga Anda. (84)

ASET TAMBAHAN
Sitelink: Galeri Proyek, Layanan Kami, Konsultasi via WhatsApp, Tentang Kami
Callout (maks. 25 karakter): Konsultasi Gratis, Tim Berpengalaman, Bahan Berkualitas, Banyak Jenis Lapangan
Telepon/pesan: 0821357570064
Catatan: callout "Melayani Banyak Jenis Lapangan" berjumlah 31 karakter, jadi dipersingkat menjadi "Banyak Jenis Lapangan" (21).

LINK WHATSAPP KONTRAKTOR
https://wa.me/62821357570064?text=Halo%20Green%20Deahan%20Sport%2C%20saya%20ingin%20konsultasi%20pembuatan%20lapangan%20olahraga.%20Boleh%20minta%20info%20lebih%20lanjut%3F

SEBELUM TAYANG
- Pakai "m2", bukan "m²", di teks iklan.
- Pastikan klaim seperti "tepat waktu" dan "tim ahli" sesuai kenyataan.
- Lacak klik WhatsApp sebagai konversi lewat Google Tag Manager.
- Setelah 1 sampai 2 minggu, cek laporan Search Terms dan sesuaikan kata kunci negatif dengan data asli.
TEXT,
            ],
            [
                'judul' => 'Google Ads: Sistem Booking Lapangan (untuk pemilik lapangan)',
                'kategori' => 'google-ads',
                'disematkan' => false,
                'isi' => <<<'TEXT'
Target: pemilik lapangan yang sudah beroperasi, bukan pemain yang mencari sewa lapangan.

STRUKTUR KAMPANYE
Tujuan: Prospek dari pemilik lapangan
Lokasi: seluruh Indonesia (layanan online, tidak terbatas jarak)

KATA KUNCI
- "aplikasi booking lapangan futsal"
- "sistem booking lapangan"
- "software booking lapangan"
- "aplikasi manajemen lapangan futsal"
- "website booking lapangan"
- "sistem booking lapangan padel"
- "aplikasi booking lapangan badminton"

KATA KUNCI NEGATIF
terdekat, dekat, murah, harga sewa, jadwal, lowongan, apk mod

"terdekat" dan "harga sewa" sangat penting dikecualikan, karena yang mencarinya adalah pemain yang ingin menyewa lapangan, bukan pemilik lapangan.

JUDUL IKLAN
- Aplikasi Booking Lapangan (25)
- Sistem Booking Futsal Online (28)
- Booking Online 24 Jam (21)
- Anti Jadwal Bentrok (19)
- Website Booking Domain Sendiri (30)
- Kelola Lapangan Lebih Mudah (27)
- Futsal, Padel, Badminton (24)
- Paket Basic, Pro, Premium (25)
- Dashboard Admin Lengkap (23)
- Green Deahan Sport (18)
- Tanya Demo via WhatsApp (23)

DESKRIPSI IKLAN
- Sistem booking online untuk lapangan futsal, padel, badminton, tenis, dan mini soccer. (86)
- Pelanggan booking sendiri 24 jam, jadwal otomatis, tanpa risiko bentrok atau dobel. (83)
- Punya halaman booking dengan subdomain atau domain sendiri, plus dashboard admin. (81)
- Pilih paket Basic, Pro, atau Premium sesuai kebutuhan. Tanya info via WhatsApp. (79)

LINK WHATSAPP SISTEM BOOKING
https://wa.me/62821357570064?text=Halo%20Green%20Deahan%20Sport%2C%20saya%20tertarik%20dengan%20sistem%20booking%20lapangan.%20Boleh%20minta%20info%20paket%20dan%20demo%3F

SEBELUM TAYANG
- Arahkan iklan ke halaman /sistem-booking, bukan beranda.
- Halaman /sistem-booking baru terlihat publik kalau SISTEM_BOOKING_PUBLIK=true di .env.
- Lacak klik WhatsApp sebagai konversi.
TEXT,
            ],
            [
                'judul' => 'Meta Ads: Kontraktor Lapangan Olahraga (umum, tanpa promo)',
                'kategori' => 'meta-ads',
                'disematkan' => false,
                'isi' => <<<'TEXT'
Tujuan kampanye: Engagement, percakapan WhatsApp (Click-to-WhatsApp). Nomor 0821357570064 harus terhubung ke Facebook Page lewat WhatsApp Business. Penempatan: Instagram Feed, Stories dan Reels, Facebook Feed, Stories dan Reels.

AUDIENS
- Calon pebisnis lapangan: usia 28 sampai 55, minat Futsal, Padel, Kewirausahaan, Usaha kecil, Investasi
- Pemilik lahan dan properti: minat Real estat, Tanah, Pengembang properti, Konstruksi
- Retargeting: pengunjung greendeahan.com dan orang yang berinteraksi dengan akun Instagram dan Facebook dalam 90 hari terakhir (butuh Meta Pixel)

TEKS UTAMA 1
Mau bangun lapangan olahraga sendiri? Green Deahan Sport siap membantu.

Kami melayani pembuatan lapangan futsal, padel, mini soccer, badminton, dan tenis, dengan hasil rapi dan bahan berkualitas.

Klik tombol di bawah untuk konsultasi gratis via WhatsApp.

TEKS UTAMA 2
Lapangan olahraga bisa jadi peluang bisnis yang menarik, apalagi kalau dibangun dengan rapi.

Green Deahan Sport membantu Anda dari konsultasi sampai lapangan siap dipakai. Lihat hasil proyek kami, lalu ceritakan rencana Anda.

Konsultasi gratis, tanpa komitmen. Chat kami sekarang.

TEKS UTAMA 3 (Stories dan Reels)
Bangun lapangan futsal, padel, atau mini soccer bersama Green Deahan Sport. Konsultasi gratis via WhatsApp.

JUDUL
- Kontraktor Lapangan Olahraga
- Bangun Lapangan Impian Anda
- Konsultasi Gratis via WhatsApp

DESKRIPSI
Futsal, padel, mini soccer

TOMBOL CTA
Kirim Pesan WhatsApp

PESAN PEMBUKA OTOMATIS DI WHATSAPP
Halo Green Deahan Sport, saya ingin konsultasi pembuatan lapangan olahraga. Boleh minta info lebih lanjut?

MATERI VISUAL
Foto dan video proyek asli adalah materi terbaik: video proses pembangunan dari tanah kosong sampai jadi, atau carousel 4 sampai 6 foto portofolio.

SEBELUM TAYANG
Pastikan klaim seperti "tepat waktu" dan "tim ahli" sesuai layanan. Siapkan tim untuk membalas chat WhatsApp dengan cepat.
TEXT,
            ],
            [
                'judul' => 'Meta Ads: Sistem Booking Lapangan (untuk pemilik lapangan)',
                'kategori' => 'meta-ads',
                'disematkan' => false,
                'isi' => <<<'TEXT'
Tujuan kampanye: Engagement, percakapan WhatsApp. Nomor 0821357570064 terhubung ke Facebook Page lewat WhatsApp Business.

AUDIENS
- Pemilik lapangan: usia 25 sampai 55, minat Futsal, Padel, Badminton, Tenis, Usaha kecil, dengan perilaku Admin Halaman Bisnis Facebook
- Retargeting: pengunjung halaman sistem booking di website

TEKS UTAMA 1
Masih catat jadwal lapangan di buku atau balas chat booking satu per satu?

Dengan sistem booking dari Green Deahan Sport, pelanggan bisa pesan lapangan sendiri 24 jam lewat halaman booking milik Anda. Jadwal otomatis, tanpa risiko bentrok.

Cocok untuk lapangan futsal, padel, badminton, tenis, dan mini soccer.

TEKS UTAMA 2
Kelola lapangan olahraga Anda lebih mudah dalam satu dashboard.

Halaman booking dengan domain sendiri, jadwal otomatis, dan pilihan paket Basic, Pro, atau Premium sesuai kebutuhan.

Chat kami untuk info paket dan demo.

TEKS UTAMA 3 (Stories dan Reels)
Pelanggan booking sendiri 24 jam, jadwal anti bentrok. Sistem booking lapangan dari Green Deahan Sport.

JUDUL
- Sistem Booking Lapangan Online
- Booking 24 Jam, Anti Bentrok
- Halaman Booking Domain Sendiri

DESKRIPSI
Paket Basic, Pro, Premium

TOMBOL CTA
Kirim Pesan WhatsApp

PESAN PEMBUKA OTOMATIS DI WHATSAPP
Halo Green Deahan Sport, saya tertarik dengan sistem booking lapangan. Boleh minta info paket dan demo?

MATERI VISUAL
Tampilkan screenshot halaman booking di layar HP dan dashboard admin, karena calon pembeli ingin melihat langsung seperti apa sistemnya.

SEBELUM TAYANG
Pastikan ketersediaan demo dan paket yang disebut memang sesuai layanan. Arahkan klik ke chat WhatsApp, bukan ke beranda.
TEXT,
            ],
        ];
    }
}
