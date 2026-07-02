<?php

namespace Database\Seeders;

use App\Models\GaleriItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Portofolio proyek nyata dipindah dari galeri.vue (green-deahan-wpnuxt),
 * situs korporat greendeahan.com sebelumnya. Bukan data contoh, ini foto
 * dan detail proyek asli yang sudah pernah dipublikasikan.
 */
class GaleriItemSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $items = [
            // Futsal
            ['kategori' => 'futsal', 'tampilan_besar' => true, 'judul' => 'Futsal Indoor Jakarta Selatan', 'kota' => 'Jakarta Selatan', 'material' => 'Lantai Interlock', 'deskripsi' => 'Lapangan futsal indoor standar internasional. Lantai interlock premium, LED 1000 lux, pagar galvanis.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/07/photo_6_2024-07-23_10-52-44.jpg'],
            ['kategori' => 'futsal', 'tampilan_besar' => false, 'judul' => 'Futsal Outdoor Bekasi', 'kota' => 'Bekasi, Jawa Barat', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Lapangan futsal outdoor rumput sintetis 40mm tahan cuaca. Sistem drainase profesional.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal2-outdoor.png'],
            ['kategori' => 'futsal', 'tampilan_besar' => false, 'judul' => 'Futsal Arena Complex Bandung', 'kota' => 'Bandung, Jawa Barat', 'material' => 'Lantai Interlock & Rumput Sintetis', 'deskripsi' => 'Kompleks futsal 2 lapangan, ruang ganti, tribun, kantin. Proyek turnkey selesai 28 hari.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/08/photo_24_2024-07-23_10-52-44.jpg'],
            ['kategori' => 'futsal', 'tampilan_besar' => true, 'judul' => 'Futsal Indoor Makassar', 'kota' => 'Makassar, Sulawesi Selatan', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Lapangan futsal indoor LED anti-silau. Kapasitas penonton 150 orang.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/07/photo_11_2024-07-23_10-52-44.jpg'],
            ['kategori' => 'futsal', 'tampilan_besar' => false, 'judul' => 'Futsal Outdoor Medan', 'kota' => 'Medan, Sumatera Utara', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Lapangan futsal outdoor lantai rumput sintetis. Finishing line cat epoxy berkualitas.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal4-outdoor-1.png'],
            ['kategori' => 'futsal', 'tampilan_besar' => false, 'judul' => 'Futsal Indoor Surabaya', 'kota' => 'Surabaya, Jawa Timur', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Lapangan futsal indoor ventilasi optimal. Dilengkapi papan skor digital di samping.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/hero-img2.jpg'],
            ['kategori' => 'futsal', 'tampilan_besar' => false, 'judul' => 'Futsal Indoor Yogyakarta', 'kota' => 'Yogyakarta', 'material' => 'Lantai Interlock', 'deskripsi' => 'Lapangan futsal indoor pusat kota Yogyakarta. LED anti-silau, tribun 80 orang.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal4-indoor.png'],
            ['kategori' => 'futsal', 'tampilan_besar' => true, 'judul' => 'Futsal Outdoor Malang', 'kota' => 'Malang, Jawa Timur', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Futsal outdoor tahan cuaca tropis. Rumput sintetis 40mm, drainase cepat, pencahayaan malam.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal5-outdoor-1.png'],
            ['kategori' => 'futsal', 'tampilan_besar' => false, 'judul' => 'Futsal Complex Bali', 'kota' => 'Denpasar, Bali', 'material' => 'Lantai Interlock', 'deskripsi' => 'Kompleks futsal 3 lapangan, lounge, kantin, parkir luas. Selesai 35 hari.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal6-indoor-1.png'],
            // Mini Soccer
            ['kategori' => 'minisoccer', 'tampilan_besar' => true, 'judul' => 'Mini Soccer Indoor Surabaya', 'kota' => 'Surabaya, Jawa Timur', 'material' => 'Rumput Sintetis Grade A', 'deskripsi' => 'Mini soccer indoor atap baja ringan. Rumput sintetis grade A 50mm, tribun 200 orang, pencahayaan malam.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer1-img.jpeg'],
            ['kategori' => 'minisoccer', 'tampilan_besar' => false, 'judul' => 'Mini Soccer Indoor Semarang', 'kota' => 'Semarang, Jawa Tengah', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Mini soccer indoor atap baja ringan. Beroperasi malam dan hujan tanpa gangguan.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer2-img.png'],
            ['kategori' => 'minisoccer', 'tampilan_besar' => false, 'judul' => 'Mini Soccer Outdoor Bali', 'kota' => 'Denpasar, Bali', 'material' => 'Rumput Sintetis Grade A', 'deskripsi' => 'Mini soccer outdoor view pantai. Material tahan garam & kelembapan tinggi. Drainase cepat.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer3-img-1.png'],
            ['kategori' => 'minisoccer', 'tampilan_besar' => false, 'judul' => 'Mini Soccer Outdoor Palembang', 'kota' => 'Palembang, Sumatera Selatan', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Mini soccer dengan tribun baja ringan & atap polycarbonate. Kapasitas 100 penonton.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer4-img-1.png'],
            ['kategori' => 'minisoccer', 'tampilan_besar' => true, 'judul' => 'Mini Soccer Indoor Balikpapan', 'kota' => 'Balikpapan, Kalimantan Timur', 'material' => 'Rumput Sintetis Grade B', 'deskripsi' => 'Mini soccer indoor desain modern. Ruang ganti 2 tim, pantry, parkir luas.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer6-img-1.png'],
            ['kategori' => 'minisoccer', 'tampilan_besar' => false, 'judul' => 'Mini Soccer Indoor Bandung', 'kota' => 'Bandung, Jawa Barat', 'material' => 'Rumput Sintetis Grade A', 'deskripsi' => 'Mini soccer indoor 2 lapangan. LED 800 lux, tribun 120 orang, AC industrial.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer66-img-1.png'],
            // Padel
            ['kategori' => 'padel', 'tampilan_besar' => true, 'judul' => 'Padel Premium Medan', 'kota' => 'Medan, Sumatera Utara', 'material' => 'Kaca Tempered + Artificial Grass', 'deskripsi' => 'Lapangan padel standar IFF. Rangka baja galvanis, kaca tempered 12mm, artificial grass 10mm.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/padel1-img-1-1.png'],
            ['kategori' => 'padel', 'tampilan_besar' => false, 'judul' => 'Padel Indoor Jakarta Pusat', 'kota' => 'Jakarta Pusat', 'material' => 'Kaca Tempered + Artificial Grass', 'deskripsi' => '2 lapangan padel indoor. LED 400 lux standar FIP, flooring padel premium import.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/padel2-img-1.png'],
            ['kategori' => 'padel', 'tampilan_besar' => false, 'judul' => 'Padel Indoor Bali', 'kota' => 'Kuta, Bali', 'material' => 'Kaca + Artificial Grass', 'deskripsi' => 'Padel indoor, kaca tempered 12mm, artificial grass 10mm.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/padel3-img-1.png'],
            // Badminton
            ['kategori' => 'badminton', 'tampilan_besar' => true, 'judul' => 'Badminton 3 Court Bandung', 'kota' => 'Bandung, Jawa Barat', 'material' => 'Interlock', 'deskripsi' => '3 lapangan badminton indoor. Lantai interlock sport premium, LED 500 lux anti-silau. Selesai 30 hari.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/badminton3-img-1.png'],
            ['kategori' => 'badminton', 'tampilan_besar' => false, 'judul' => 'Badminton 2 Court Semarang', 'kota' => 'Semarang, Jawa Tengah', 'material' => 'Interlock', 'deskripsi' => '2 lapangan badminton lantai interlock. Akustik ruangan baik, non-slip, ramah lutut.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/badminton2-img-1.png'],
            ['kategori' => 'badminton', 'tampilan_besar' => false, 'judul' => 'Badminton 3 Court Mojokerto', 'kota' => 'Mojokerto, Jawa Timur', 'material' => 'Lantai Vinyl', 'deskripsi' => '3 lapangan badminton indoor. Lantai vinyl, non-slip, ramah lutut. Selesai 14 hari.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/badmintoon-img4.png'],
            // Proses Konstruksi
            ['kategori' => 'proses', 'tampilan_besar' => false, 'judul' => 'Survey Lokasi, Sebelum Mulai', 'kota' => 'Berbagai Kota', 'material' => 'Survey & Pengukuran', 'deskripsi' => 'Tim kami survey lokasi, ukur lahan, dan analisis tanah sebelum konstruksi dimulai.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/konstruksi11-img-1.png'],
            ['kategori' => 'proses', 'tampilan_besar' => true, 'judul' => 'Pengerjaan Struktur Dasar', 'kota' => 'On Progress', 'material' => 'Konstruksi', 'deskripsi' => 'Pengerjaan pondasi dan struktur dasar lapangan. Material berkualitas, pengawasan ketat setiap tahap.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/07/photo_28_2024-07-23_10-52-44.webp'],
            ['kategori' => 'proses', 'tampilan_besar' => false, 'judul' => 'Pemasangan Lantai Interlock', 'kota' => 'On Progress', 'material' => 'Lantai Interlock', 'deskripsi' => 'Pemasangan lantai interlock presisi. Setiap tile dipasang cermat untuk hasil rata dan rapi.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/07/interlock1.jpg'],
            ['kategori' => 'proses', 'tampilan_besar' => false, 'judul' => 'Pengiriman Material Lapangan', 'kota' => 'Logistik', 'material' => 'Material & Logistik', 'deskripsi' => 'Pengiriman material ke seluruh Indonesia. Kami handle logistik sepenuhnya untuk Anda.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/konstruksi2-img.png'],
            ['kategori' => 'proses', 'tampilan_besar' => false, 'judul' => 'Pemasangan Rumput Sintetis', 'kota' => 'On Progress', 'material' => 'Rumput Sintetis', 'deskripsi' => 'Pemasangan rumput sintetis tahap demi tahap. Jahitan presisi, permukaan rata, hasil rapi.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/konstruksi22-img-1.png'],
            ['kategori' => 'proses', 'tampilan_besar' => true, 'judul' => 'Proses Pembuatan Lantai Interlock', 'kota' => 'Dokumentasi', 'material' => 'Interlock Premium', 'deskripsi' => 'Lantai interlock kuat & tahan lama, diproduksi presisi di pabrik Surabaya.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/konstruksi6-img.png'],
            ['kategori' => 'proses', 'tampilan_besar' => false, 'judul' => 'Konstruksi Lapangan Mini Soccer 30x50m', 'kota' => 'On Progress', 'material' => 'Rumput Sintetis & Struktur Baja', 'deskripsi' => 'Proyek lapangan mini soccer 30x50m. Struktur baja ringan, rumput sintetis premium, finishing line marking.', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/06/konstruksi-06.jpeg'],
        ];

        foreach ($items as $urutan => $item) {
            GaleriItem::updateOrCreate(
                ['judul' => $item['judul']],
                array_merge($item, ['urutan' => $urutan + 1, 'status_aktif' => true]),
            );
        }
    }
}
