<?php

namespace Database\Seeders;

use App\Models\Artikel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Artikel blog asli dipindah dari blog.vue (green-deahan-wpnuxt), diambil
 * langsung dari backend WordPress situs korporat greendeahan.com
 * (gdlogin.greendeahan.com/graphql) yang masih aktif. Bukan konten contoh.
 */
class ArtikelSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $artikel = [
            ['judul' => 'Cara Hitung ROI Bisnis Lapangan Olahraga: Kapan Balik Modal?', 'slug' => 'cara-hitung-roi-bisnis-lapangan-olahraga-kapan-balik-modal', 'kategori' => 'Panduan Bisnis', 'ringkasan' => 'Sebelum memutuskan investasi, sangat penting untuk menghitung Return on Investment (ROI) agar Anda tahu kapan modal bisa kembali dan berapa keuntungan yang bisa diharapkan. Rumus Dasar ROI ROI = (Pendapatan Bersih Tahunan / Total Investasi) × 100% Contoh Kalkulasi Lapangan Futsal Total investasi: Rp 400 juta Harga sewa: Rp 120.000/jam Rata-rata booking: 10 jam/hari Pendapatan [...]', 'konten' => '<p>Sebelum memutuskan investasi, sangat penting untuk menghitung Return on Investment (ROI) agar Anda tahu kapan modal bisa kembali dan berapa keuntungan yang bisa diharapkan.</p>
<h2>Rumus Dasar ROI</h2>
<p><strong>ROI = (Pendapatan Bersih Tahunan / Total Investasi) × 100%</strong></p>
<h2>Contoh Kalkulasi Lapangan Futsal</h2>
<ul>
<li>Total investasi: Rp 400 juta</li>
<li>Harga sewa: Rp 120.000/jam</li>
<li>Rata-rata booking: 10 jam/hari</li>
<li>Pendapatan kotor: Rp 1,2 juta/hari × 30 = Rp 36 juta/bulan</li>
<li>Biaya operasional (listrik, gaji, perawatan): ~Rp 8 juta/bulan</li>
<li>Pendapatan bersih: ~Rp 28 juta/bulan = Rp 336 juta/tahun</li>
<li><strong>ROI: 84% per tahun → Balik modal ~14 bulan</strong></li>
</ul>
<blockquote><p>💡 Angka ini bisa lebih cepat jika lokasi strategis atau Anda menambahkan pendapatan dari kantin, locker rental, coaching, dan membership.</p></blockquote>
<h2>Faktor yang Mempengaruhi ROI</h2>
<ul>
<li>Lokasi lapangan (dekat permukiman, kampus, kantor)</li>
<li>Harga sewa yang kompetitif</li>
<li>Kualitas fasilitas pendukung</li>
<li>Strategi pemasaran (media sosial, partnership komunitas)</li>
<li>Jam operasional (extended hours = lebih banyak slot)</li>
</ul>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/roi-img.jpg', 'tanggal_terbit' => '2026-05-03'],
            ['judul' => '7 Cara Merawat Lapangan Futsal agar Awet Sampai 10 Tahun', 'slug' => '7-cara-merawat-lapangan-futsal-agar-awet-sampai-10-tahun', 'kategori' => 'Tips Perawatan', 'ringkasan' => 'Investasi lapangan futsal bisa bertahan lama jika dirawat dengan benar. Berikut 7 tips dari tim GreenDeahan berdasarkan pengalaman 16+ tahun di lapangan. 1. Bersihkan Secara Rutin Sapu atau vacuum lantai setiap hari sebelum dan sesudah jam operasional. Debu dan pasir yang menumpuk bisa menggores permukaan lantai. 2. Larang Sepatu Non-Futsal Pasang aturan wajib sepatu futsal. [...]', 'konten' => '<p>Investasi lapangan futsal bisa bertahan lama jika dirawat dengan benar. Berikut 7 tips dari tim GreenDeahan berdasarkan pengalaman 16+ tahun di lapangan.</p>
<h2>1. Bersihkan Secara Rutin</h2>
<p>Sapu atau vacuum lantai setiap hari sebelum dan sesudah jam operasional. Debu dan pasir yang menumpuk bisa menggores permukaan lantai.</p>
<h2>2. Larang Sepatu Non-Futsal</h2>
<p>Pasang aturan wajib sepatu futsal. Sepatu dengan sol keras atau berpaku bisa merusak permukaan interlock maupun rumput sintetis dengan cepat.</p>
<h2>3. Cek Infill Rumput Sintetis Setiap 6 Bulan</h2>
<p>Untuk rumput sintetis, pasir silika infill perlu ditambah setiap 6 bulan agar serat rumput tetap tegak dan permukaan tetap rata.</p>
<h2>4. Periksa Sambungan Tile Interlock</h2>
<p>Cek sambungan antar tile setiap 3 bulan. Tile yang longgar atau naik bisa menjadi risiko cedera pemain dan harus segera diperbaiki.</p>
<h2>5. Cat Ulang Garis Lapangan</h2>
<p>Garis lapangan yang pudar tidak hanya mengurangi estetika tapi juga membingungkan pemain. Cat ulang setiap 1–2 tahun dengan cat epoxy berkualitas.</p>
<h2>6. Perawatan Pencahayaan</h2>
<p>Bersihkan lampu LED dari debu setiap bulan. Lampu yang kotor bisa mengurangi intensitas cahaya hingga 30%. Ganti segera jika ada yang mati.</p>
<h2>7. Gunakan Jasa Servis Berkala</h2>
<blockquote><p>💡 GreenDeahan menyediakan layanan maintenance berkala untuk memastikan lapangan Anda selalu dalam kondisi prima. Hubungi kami untuk paket perawatan tahunan.</p></blockquote>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/07/interlock1.jpg', 'tanggal_terbit' => '2026-05-03'],
            ['judul' => 'Ukuran Standar Lapangan Futsal Indoor & Outdoor Sesuai FIFA', 'slug' => 'ukuran-standar-lapangan-futsal-indoor-outdoor-sesuai-fifa', 'kategori' => 'Futsal', 'ringkasan' => 'Lapangan futsal yang sesuai standar akan meningkatkan kepercayaan pelanggan dan memungkinkan Anda menggelar turnamen resmi. Ukuran Standar FIFA Panjang: 25–42 meter (internasional: 38–42 meter) Lebar: 15–25 meter (internasional: 18–22 meter) Area penjaga gawang: 6 meter × 3 meter Titik penalti: 6 meter dari garis gawang Titik penalti kedua: 10 meter dari garis gawang Ukuran yang [...]', 'konten' => '<p>Lapangan futsal yang sesuai standar akan meningkatkan kepercayaan pelanggan dan memungkinkan Anda menggelar turnamen resmi.</p>
<h2>Ukuran Standar FIFA</h2>
<ul>
<li><strong>Panjang:</strong> 25–42 meter (internasional: 38–42 meter)</li>
<li><strong>Lebar:</strong> 15–25 meter (internasional: 18–22 meter)</li>
<li><strong>Area penjaga gawang:</strong> 6 meter × 3 meter</li>
<li><strong>Titik penalti:</strong> 6 meter dari garis gawang</li>
<li><strong>Titik penalti kedua:</strong> 10 meter dari garis gawang</li>
</ul>
<h2>Ukuran yang Paling Umum Dipakai di Indonesia</h2>
<p>Lapangan futsal komersial di Indonesia umumnya berukuran <strong>20 × 40 meter</strong> — optimal antara standar FIFA dan efisiensi lahan.</p>
<h2>Tips Optimasi Lahan Sempit</h2>
<ul>
<li>Lahan minimal 22 × 42 meter (sudah termasuk area bebas 1 meter per sisi)</li>
<li>Jika lahan lebih kecil, pertimbangkan futsal 3v3 dengan ukuran mini</li>
<li>Konsultasikan dengan tim GreenDeahan untuk solusi desain terbaik</li>
</ul>
<blockquote><p>💡 Kami pernah mengoptimalkan lahan selebar 18 meter menjadi lapangan futsal yang fungsional dan nyaman. Hubungi kami untuk konsultasi desain gratis!</p></blockquote>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal-court1.webp', 'tanggal_terbit' => '2026-04-28'],
            ['judul' => 'Cara Merawat Rumput Sintetis agar Tidak Cepat Rusak dan Pudar', 'slug' => 'cara-merawat-rumput-sintetis-agar-tidak-cepat-rusak-dan-pudar', 'kategori' => 'Tips Perawatan', 'ringkasan' => 'Rumput sintetis yang terpasang dengan baik seharusnya bisa bertahan 6–10 tahun. Namun tanpa perawatan yang tepat, usia pakainya bisa jauh lebih pendek. Perawatan Harian Sapu atau blower permukaan dari daun, debu, dan sampah Siram dengan air bersih jika terlalu panas (terutama saat siang hari outdoor) Periksa ada tidaknya benda tajam yang tertinggal di permukaan Perawatan [...]', 'konten' => '<p>Rumput sintetis yang terpasang dengan baik seharusnya bisa bertahan 6–10 tahun. Namun tanpa perawatan yang tepat, usia pakainya bisa jauh lebih pendek.</p>
<h2>Perawatan Harian</h2>
<ul>
<li>Sapu atau blower permukaan dari daun, debu, dan sampah</li>
<li>Siram dengan air bersih jika terlalu panas (terutama saat siang hari outdoor)</li>
<li>Periksa ada tidaknya benda tajam yang tertinggal di permukaan</li>
</ul>
<h2>Perawatan Bulanan</h2>
<ul>
<li>Brush permukaan rumput dengan sikat khusus untuk menegakkan serat yang rebah</li>
<li>Periksa kondisi jahitan sambungan rumput</li>
<li>Pastikan sistem drainase tidak tersumbat</li>
</ul>
<h2>Perawatan 6 Bulanan</h2>
<ul>
<li>Tambah infill pasir silika jika sudah berkurang</li>
<li>Cek kondisi underlayer (lapisan bawah rumput)</li>
<li>Lakukan deep cleaning dengan alat khusus</li>
</ul>
<blockquote><p>💡 GreenDeahan menyediakan paket servis berkala rumput sintetis. Kami datang ke lokasi Anda untuk melakukan perawatan komprehensif. Tanya harga paket via WhatsApp!</p></blockquote>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal1-outdoor.png', 'tanggal_terbit' => '2026-04-03'],
            ['judul' => 'Lantai Interlock vs Rumput Sintetis: Mana yang Lebih Cocok untuk Futsal Anda?', 'slug' => 'lantai-interlock-vs-rumput-sintetis-mana-yang-lebih-cocok-untuk-futsal-anda', 'kategori' => 'Material', 'ringkasan' => 'Pilihan lantai adalah keputusan paling krusial dalam membangun lapangan futsal. Dua opsi paling populer adalah lantai interlock dan rumput sintetis — keduanya punya keunggulan masing-masing. Lantai Interlock Harga: Lebih terjangkau, Rp 80–150 juta untuk 1 lapangan Daya tahan: 10–15 tahun dengan perawatan minimal Keunggulan: Jika satu tile rusak, bisa diganti sebagian saja Kekurangan: Kurang estetik [...]', 'konten' => '<p>Pilihan lantai adalah keputusan paling krusial dalam membangun lapangan futsal. Dua opsi paling populer adalah lantai interlock dan rumput sintetis — keduanya punya keunggulan masing-masing.</p>
<h2>Lantai Interlock</h2>
<ul>
<li><strong>Harga:</strong> Lebih terjangkau, Rp 80–150 juta untuk 1 lapangan</li>
<li><strong>Daya tahan:</strong> 10–15 tahun dengan perawatan minimal</li>
<li><strong>Keunggulan:</strong> Jika satu tile rusak, bisa diganti sebagian saja</li>
<li><strong>Kekurangan:</strong> Kurang estetik untuk outdoor, terasa keras jika jatuh</li>
<li><strong>Cocok untuk:</strong> Indoor, budget terbatas, area urban padat</li>
</ul>
<h2>Rumput Sintetis</h2>
<ul>
<li><strong>Harga:</strong> Lebih mahal, Rp 130–250 juta untuk 1 lapangan</li>
<li><strong>Daya tahan:</strong> 5–8 tahun tergantung intensitas penggunaan</li>
<li><strong>Keunggulan:</strong> Estetik, nyaman, cocok outdoor, photogenic</li>
<li><strong>Kekurangan:</strong> Lebih mahal, perlu infill pasir silika berkala</li>
<li><strong>Cocok untuk:</strong> Outdoor, target pasar premium, area rekreasi</li>
</ul>
<blockquote><p>💡 <strong>Rekomendasi GreenDeahan:</strong> Untuk bisnis pertama dengan modal terbatas → pilih interlock. Untuk lokasi outdoor atau target pasar menengah ke atas → rumput sintetis.</p></blockquote>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/interlock-rumput-sintetis.png', 'tanggal_terbit' => '2026-03-01'],
            ['judul' => 'Vinyl Sport vs Kayu Keras untuk Lapangan Badminton: Pilih yang Mana?', 'slug' => 'vinyl-sport-vs-kayu-keras-untuk-lapangan-badminton-pilih-yang-mana', 'kategori' => 'Badminton', 'ringkasan' => 'Pilihan lantai sangat menentukan kenyamanan pemain dan umur lapangan badminton Anda. Mari bandingkan dua pilihan paling populer. Vinyl Sport Harga: Rp 120–200 juta untuk 2 court Keunggulan: Non-slip, shock absorption baik, ramah lutut, mudah dibersihkan Warna: Tersedia banyak pilihan warna dan motif Umur: 8–12 tahun Standar: Digunakan di banyak arena BWF Kayu Keras (Hardwood) Harga: [...]', 'konten' => '<p>Pilihan lantai sangat menentukan kenyamanan pemain dan umur lapangan badminton Anda. Mari bandingkan dua pilihan paling populer.</p>
<h2>Vinyl Sport</h2>
<ul>
<li><strong>Harga:</strong> Rp 120–200 juta untuk 2 court</li>
<li><strong>Keunggulan:</strong> Non-slip, shock absorption baik, ramah lutut, mudah dibersihkan</li>
<li><strong>Warna:</strong> Tersedia banyak pilihan warna dan motif</li>
<li><strong>Umur:</strong> 8–12 tahun</li>
<li><strong>Standar:</strong> Digunakan di banyak arena BWF</li>
</ul>
<h2>Kayu Keras (Hardwood)</h2>
<ul>
<li><strong>Harga:</strong> Rp 180–350 juta untuk 2 court</li>
<li><strong>Keunggulan:</strong> Pantulan bola lebih natural, estetik premium, tahan lama</li>
<li><strong>Kekurangan:</strong> Lebih mahal, perlu perawatan poles berkala, sensitif terhadap kelembapan</li>
<li><strong>Umur:</strong> 15–25 tahun dengan perawatan baik</li>
</ul>
<blockquote><p>💡 <strong>Rekomendasi:</strong> Untuk bisnis skala menengah → vinyl sport lebih cost-effective. Untuk arena premium atau pelatihan atlet → kayu keras memberikan pengalaman bermain terbaik.</p></blockquote>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/badmintoon-img2.png', 'tanggal_terbit' => '2026-02-08'],
            ['judul' => 'Ukuran Standar Lapangan Mini Soccer: Panduan Lengkap Sebelum Bangun', 'slug' => 'ukuran-standar-lapangan-mini-soccer-panduan-lengkap-sebelum-bangun', 'kategori' => 'Mini Soccer', 'ringkasan' => 'Membangun lapangan mini soccer yang tidak sesuai ukuran standar bisa merugikan bisnis Anda jangka panjang. Berikut panduan ukuran resmi yang kami gunakan. Ukuran Lapangan Mini Soccer Panjang: 30 – 45 meter Lebar: 18 – 25 meter Area penjaga gawang: 5 x 3 meter Titik penalti: 6 meter dari garis gawang Ukuran Lahan Minimal yang Dibutuhkan [...]', 'konten' => '<p>Membangun lapangan mini soccer yang tidak sesuai ukuran standar bisa merugikan bisnis Anda jangka panjang. Berikut panduan ukuran resmi yang kami gunakan.</p>
<h2>Ukuran Lapangan Mini Soccer</h2>
<ul>
<li><strong>Panjang:</strong> 30 – 45 meter</li>
<li><strong>Lebar:</strong> 18 – 25 meter</li>
<li><strong>Area penjaga gawang:</strong> 5 x 3 meter</li>
<li><strong>Titik penalti:</strong> 6 meter dari garis gawang</li>
</ul>
<h2>Ukuran Lahan Minimal yang Dibutuhkan</h2>
<p>Tambahkan 2–3 meter di setiap sisi untuk area bebas, pagar, dan jalur pemain. Total lahan minimal sekitar 35 x 25 meter untuk 1 lapangan.</p>
<h2>Spesifikasi Rumput Sintetis Mini Soccer</h2>
<ul>
<li>Ketebalan serat: 40–55mm</li>
<li>Infill: Pasir silika + rubber crumb</li>
<li>Ketahanan UV minimal 8 tahun</li>
<li>Drainase: Min. 800mm per jam</li>
</ul>
<blockquote><p>💡 <strong>Tips:</strong> Konsultasikan ukuran lahan Anda dengan tim GreenDeahan sebelum memutuskan. Kami akan bantu optimalkan desain sesuai lahan yang tersedia.</p></blockquote>
<h2>Fasilitas Pendukung yang Disarankan</h2>
<ul>
<li>Tribun penonton (kapasitas 50–200 orang)</li>
<li>Ruang ganti 2 tim</li>
<li>Toilet umum</li>
<li>Pencahayaan malam (min. 500 lux)</li>
<li>Area parkir</li>
</ul>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer3-img-1.png', 'tanggal_terbit' => '2026-02-03'],
            ['judul' => 'Mengapa Lapangan Padel Jadi Investasi Paling Menjanjikan di 2026', 'slug' => 'mengapa-lapangan-padel-jadi-investasi-paling-menjanjikan-di-2026', 'kategori' => 'Padel', 'ringkasan' => 'Padel bukan sekadar tren sesaat. Olahraga ini sedang mengalami pertumbuhan eksplosif di Indonesia, mengikuti jejak negara-negara Amerika Latin dan Eropa di mana padel sudah menjadi olahraga rakyat. Kenapa Padel Booming? Mudah dipelajari untuk semua usia, bahkan pemula bisa langsung seru bermain Dimainkan 4 orang (doubles), sehingga lebih sosial dan ramai Tidak terlalu menguras fisik seperti [...]', 'konten' => '<p>Padel bukan sekadar tren sesaat. Olahraga ini sedang mengalami pertumbuhan eksplosif di Indonesia, mengikuti jejak negara-negara Amerika Latin dan Eropa di mana padel sudah menjadi olahraga rakyat.</p>
<h2>Kenapa Padel Booming?</h2>
<ul>
<li>Mudah dipelajari untuk semua usia, bahkan pemula bisa langsung seru bermain</li>
<li>Dimainkan 4 orang (doubles), sehingga lebih sosial dan ramai</li>
<li>Tidak terlalu menguras fisik seperti tenis, cocok untuk segala usia</li>
<li>Estetik dan photogenic — viral di media sosial</li>
</ul>
<h2>Potensi Bisnis Lapangan Padel</h2>
<p>Harga sewa lapangan padel bisa 1,5x–2x lebih mahal dari futsal biasa. Di kota besar, sewa per jam bisa mencapai Rp 150.000 – 300.000. Dengan 2 lapangan saja, pendapatan bisa Rp 50–90 juta per bulan.</p>
<blockquote><p>💡 <strong>Peluang emas:</strong> Di banyak kota tier-2 Indonesia, belum ada lapangan padel sama sekali. Menjadi yang pertama di kota Anda = keunggulan kompetitif besar!</p></blockquote>
<h2>Biaya Konstruksi Padel</h2>
<p>1 lapangan padel standar internasional bisa dibangun dengan investasi Rp 250 – 450 juta. Lebih mahal dari futsal, namun return-nya jauh lebih tinggi dan segmen pasarnya premium.</p>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/padel2-img-1.png', 'tanggal_terbit' => '2026-01-23'],
            ['judul' => 'Mengenal Spesifikasi Lapangan Padel Standar Internasional (IFF)', 'slug' => 'mengenal-spesifikasi-lapangan-padel-standar-internasional-iff', 'kategori' => 'Padel', 'ringkasan' => 'Jika Anda ingin membangun lapangan padel yang bisa digunakan untuk kompetisi dan mendapat kepercayaan pemain serius, wajib mengacu pada standar IFF (International Padel Federation). Dimensi Lapangan Panjang: 20 meter Lebar: 10 meter Tinggi minimum: 6 meter (indoor) Tinggi kaca belakang: 3 meter Tinggi kaca samping: 3 meter (bagian belakang) + pagar metal 1 meter Spesifikasi [...]', 'konten' => '<p>Jika Anda ingin membangun lapangan padel yang bisa digunakan untuk kompetisi dan mendapat kepercayaan pemain serius, wajib mengacu pada standar IFF (International Padel Federation).</p>
<h2>Dimensi Lapangan</h2>
<ul>
<li><strong>Panjang:</strong> 20 meter</li>
<li><strong>Lebar:</strong> 10 meter</li>
<li><strong>Tinggi minimum:</strong> 6 meter (indoor)</li>
<li><strong>Tinggi kaca belakang:</strong> 3 meter</li>
<li><strong>Tinggi kaca samping:</strong> 3 meter (bagian belakang) + pagar metal 1 meter</li>
</ul>
<h2>Spesifikasi Material</h2>
<ul>
<li><strong>Kaca:</strong> Tempered safety glass minimum 10mm, sebaiknya 12mm</li>
<li><strong>Rangka:</strong> Baja galvanis atau aluminium ekstrusi anti karat</li>
<li><strong>Lantai:</strong> Artificial grass padel 8–12mm dengan infill silika</li>
<li><strong>Net:</strong> Tinggi 88cm di tengah, 92cm di sisi</li>
</ul>
<h2>Pencahayaan Standar</h2>
<p>Minimum 400 lux untuk penggunaan umum, 500–750 lux untuk kompetisi. Gunakan lampu LED dengan CRI (Color Rendering Index) minimum 80.</p>
<blockquote><p>💡 GreenDeahan adalah spesialis lapangan padel di Indonesia. Semua proyek padel kami mengacu pada standar IFF. Konsultasi gratis untuk estimasi biaya dan desain!</p></blockquote>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/padel-img1.png', 'tanggal_terbit' => '2026-01-03'],
            ['judul' => 'Berapa Modal Membangun Lapangan Futsal? Rincian Biaya Lengkap 2026', 'slug' => 'berapa-modal-membangun-lapangan-futsal-rincian-biaya-lengkap-2026', 'kategori' => 'Panduan Bisnis', 'ringkasan' => '1. Komponen Biaya Utama Sewa/beli lahan: Tergantung lokasi. Di kota besar bisa Rp 15–50 juta/bulan untuk sewa. Beli lahan mulai Rp 300 juta – 1 miliar. Konstruksi lapangan: Rp 150 – 400 juta tergantung ukuran, material, dan fasilitas. Lantai (interlock/rumput sintetis): Rp 80 – 200 juta untuk 1 lapangan standar. Pencahayaan LED: Rp 20 – [...]', 'konten' => '<h2>1. Komponen Biaya Utama</h2>
<ul>
<li><strong>Sewa/beli lahan:</strong> Tergantung lokasi. Di kota besar bisa Rp 15–50 juta/bulan untuk sewa. Beli lahan mulai Rp 300 juta – 1 miliar.</li>
<li><strong>Konstruksi lapangan:</strong> Rp 150 – 400 juta tergantung ukuran, material, dan fasilitas.</li>
<li><strong>Lantai (interlock/rumput sintetis):</strong> Rp 80 – 200 juta untuk 1 lapangan standar.</li>
<li><strong>Pencahayaan LED:</strong> Rp 20 – 50 juta untuk sistem lighting 1000 lux.</li>
<li><strong>Pagar, gawang, net:</strong> Rp 25 – 60 juta.</li>
<li><strong>Ruang ganti &amp; toilet:</strong> Rp 30 – 80 juta (opsional tapi sangat disarankan).</li>
</ul>
<blockquote><p>💡 <strong>Tips GreenDeahan:</strong> Untuk modal awal yang efisien, pilih lantai interlock yang lebih terjangkau dibanding rumput sintetis namun sama awetnya. Anda bisa upgrade nanti setelah bisnis berkembang.</p></blockquote>
<h2>2. Total Estimasi Modal</h2>
<ul>
<li><strong>Skala kecil (tanpa beli lahan):</strong> Rp 200 – 350 juta</li>
<li><strong>Skala menengah (dengan fasilitas lengkap):</strong> Rp 400 – 700 juta</li>
<li><strong>Skala besar (kompleks multi-lapangan):</strong> Rp 800 juta – 2 miliar</li>
</ul>
<h2>3. Estimasi Balik Modal</h2>
<p>Dengan harga sewa per jam Rp 100.000 – 200.000 dan rata-rata 10 jam booking per hari, pendapatan bisa mencapai Rp 30 – 60 juta per bulan. Balik modal bisa dicapai dalam 12 – 24 bulan.</p>
<h2>4. Sistem Pembayaran GreenDeahan</h2>
<p>Kami memudahkan Anda dengan sistem DP di awal dan pelunasan setelah lapangan selesai 100%. Tidak perlu keluar modal besar sekaligus. Hubungi kami untuk simulasi RAB sesuai kebutuhan Anda.</p>
', 'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/futsal-img2.png', 'tanggal_terbit' => '2026-01-01'],        ];

        foreach ($artikel as $item) {
            Artikel::updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, ['status_aktif' => true]),
            );
        }
    }
}
