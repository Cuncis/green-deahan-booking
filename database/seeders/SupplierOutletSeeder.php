<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Daftar outlet supplier (RANK Sports) beserta kode pos asal pengiriman.
 * Platform shopee, link toko (Google Maps) di shop_url, WhatsApp bawaan
 * 6281279997779 bila kosong, catatan hanya bila ada. Kode pos berbasis area
 * outlet (kebanyakan perkiraan), dipakai untuk ongkir RajaOngkir dan Biteship. Baris "Area: ..."
 * di catatan dipakai impor HTML produk untuk memilih supplier otomatis dari
 * "Dikirim Dari".
 *
 * Aman dijalankan ulang: kode pos, link peta, dan catatan diperbarui, tetapi
 * nomor WhatsApp yang sudah diisi admin tidak ditimpa.
 *
 * php artisan db:seed --class=SupplierOutletSeeder
 */
class SupplierOutletSeeder extends Seeder
{
    /** Nomor bawaan bila outlet belum punya nomor sendiri. */
    private const WHATSAPP_BAWAAN = '6281279997779';

    private const CSV = <<<'CSV'
DKI Jakarta|RANK Sports Green Lake City, Jakarta Barat|Duri Kosambi, Cengkareng|11750|Approximate||https://maps.app.goo.gl/cWH2qNuyikkfzCdT7
DKI Jakarta|RANK Sports Ciledug, Jakarta Selatan|Petukangan Utara, Pesanggrahan|12260|Approximate|Jika toko ada di Ciledug (Kota Tangerang), kode pos sekitar 15151|https://maps.app.goo.gl/Z4mdCGMZXX3mTJhB7
DKI Jakarta|RANK Sports Condet, Jakarta Selatan|Balekambang, Kramat Jati (Jakarta Timur)|13530|Approximate|Condet ada di Jakarta Timur|https://maps.app.goo.gl/1ApRYeNujQjZsziG6
DKI Jakarta|RANK Sports Tj. Priok, Jakarta Utara|Tanjung Priok|14310|Approximate||https://maps.app.goo.gl/j913xW3qosyBWfhV6
DKI Jakarta|RANK Sports Tanjung Duren, Jakarta Barat|Tanjung Duren, Grogol Petamburan|11470|Approximate||https://maps.app.goo.gl/K2s2NNQ5t5hB9xwi6
DKI Jakarta|RANK Sports Kalideres, Jakarta Barat|Kalideres|11840|Approximate||https://maps.app.goo.gl/p5MAQmb7n7XAt7b7A
DKI Jakarta|RANK Sports Cempaka Putih, Jakarta Pusat|Cempaka Putih Timur|10510|Approximate||https://maps.app.goo.gl/Js3it6nG8s9H9eZ36
DKI Jakarta|RANK Sports Fatmawati, Jakarta Selatan|Cilandak Barat|12430|Approximate||https://maps.app.goo.gl/NSKLdhDaqGHUjPX6A
Banten|RANK Sports Tangerang, Kota Tangerang|Kota Tangerang|15111|Approximate||https://maps.app.goo.gl/yQj2x6AWeCNm2q9C6
Banten|RANK Sports Pamulang, Tangerang Selatan|Pamulang|15417|Approximate||https://maps.app.goo.gl/AV4o8EAJWjgkuqdUA
Banten|RANK Sports Serang, Banten|Kota Serang|42111|Approximate||https://maps.app.goo.gl/cJ9icVeacZ3HRVNZ8
Banten|RANK Sports Bintaro, Tangsel|Pondok Aren|15224|Approximate||https://maps.app.goo.gl/RwaAWs6wCtxAxV2V9
Banten|RANK Sports Cikupa, Tangerang|Cikupa|15710|Approximate||https://maps.app.goo.gl/zr32zWDHkWZe1nuR7
Banten|RANK Sports Serpong, Tangerang Selatan|Serpong|15310|Approximate||https://maps.app.goo.gl/GUkBLKNvkmu3E1pG6
Jawa Barat|RANK Sports Cibinong, Bogor|Cibinong, Kab. Bogor|16911|Approximate||https://maps.app.goo.gl/EbbRA7Ah4K3PtokN8
Jawa Barat|RANK Sports Parung, Kabupaten Bogor|Parung|16330|Approximate||https://maps.app.goo.gl/H1CAdgGYnZc39bq47
Jawa Barat|RANK Sports Bekasi, Kota Bekasi|Kota Bekasi|17111|Approximate||https://maps.app.goo.gl/V9XfcatJEYjGmSJX7
Jawa Barat|RANK Sports Cikampek, Jawa Barat|Cikampek|41373|Approximate||https://maps.app.goo.gl/b8w26RRD2844NUqN8
Jawa Barat|RANK Sports Purwakarta, Jawa Barat|Purwakarta|41111|Approximate||https://maps.app.goo.gl/NuWxmQoYP99gX26x8
Jawa Barat|RANK Sports Sukabumi, Jawa Barat|Kota Sukabumi|43111|Approximate||https://maps.app.goo.gl/PuhbCqd2bsqFBu1u8
Jawa Barat|RANK Sports Subang, Jawa Barat|Subang|41211|Approximate||https://maps.app.goo.gl/9yj5kjT7EBhYaurW6
Jawa Barat|RANK Sports Cibubur, Jawa Barat|Harjamukti, Cimanggis|16454|Approximate|Cibubur mencakup beberapa kota, perlu dicek|https://maps.app.goo.gl/bAGUAdSpL7ta1zGc7
Jawa Barat|RANK Sports Cikarang, Jawa Barat|Cikarang Utara|17530|Approximate||https://maps.app.goo.gl/Xt134cnPDSJwuUAT9
Jawa Barat|RANK Sports Cirebon, Jawa Barat|Kota Cirebon|45111|Approximate||https://maps.app.goo.gl/RF9GfNu4mmRJTFe77
Jawa Barat|RANK Sports Bogor Kota, Jawa Barat|Bogor Tengah|16121|Approximate||https://maps.app.goo.gl/md33uq7YzQaYaM1H9
Jawa Barat|RANK Sports Cianjur, Jawa Barat|Cianjur|43211|Approximate||https://maps.app.goo.gl/WxhW8c48Le8vT1hV8
Jawa Barat|RANK Sports Tambun, Jawa Barat|Tambun Selatan|17510|Approximate|Link peta sama dengan Harapan Indah|https://maps.app.goo.gl/Br2veyDAb5ywntrL8
Jawa Barat|RANK Sports Padalarang, Jawa Barat|Padalarang|40553|Approximate||https://maps.app.goo.gl/wRBe3FqVJfogUG7U8
Jawa Barat|RANK Sports Tasikmalaya, Jawa Barat|Kota Tasikmalaya|46111|Approximate||https://maps.app.goo.gl/rcRWVCRwXPVBCLKf6
Jawa Barat|RANK Sports Garut, Jawa Barat|Garut Kota|44111|Approximate||https://maps.app.goo.gl/KTVUsPK94enDPq7PA
Jawa Barat|RANK Sports Kuningan, Jawa Barat|Kuningan|45511|Approximate||https://maps.app.goo.gl/SrtCUeU9ddoJ6Eot6
Jawa Barat|RANK Sports Majalengka, Jawa Barat|Majalengka|45411|Approximate||https://maps.app.goo.gl/jyfKVouDYYPLj9Z2A
Jawa Barat|RANK Sports Kopo Bandung, Jawa Barat|Kopo, Bojongloa Kaler|40233|Approximate||https://maps.app.goo.gl/CadjVByt32YYVk659
Jawa Barat|RANK Sports Zamrud Mustika Jaya, Jawa Barat|Mustika Jaya, Bekasi|17155|Approximate||https://maps.app.goo.gl/FrRod5XS92oGvX3M6
Jawa Barat|RANK Sports Karawang, Jawa Barat|Karawang Barat|41311|Approximate||https://maps.app.goo.gl/KVDFnhS89jQTBbkQ9
Jawa Barat|RANK Sports Sumedang, Jawa Barat|Sumedang Selatan|45311|Approximate||https://maps.app.goo.gl/jMbGiVA6D4mbkxjX9
Jawa Barat|RANK Sports Bandung Antapani, Jawa Barat|Antapani|40291|Approximate||https://maps.app.goo.gl/LiZuDjDQbbs3N9Eu9
Jawa Barat|RANK Sports Depok, Jawa Barat|Pancoran Mas|16431|Approximate||https://maps.app.goo.gl/SBuZHoWRWLPbdLb59
Jawa Barat|RANK Sports Harapan Indah Bekasi, Jawa Barat|Pejuang, Medan Satria|17131|Approximate|Link peta sama dengan Tambun|https://maps.app.goo.gl/Br2veyDAb5ywntrL8
Jawa Tengah|RANK Sports Tegal, Jawa Tengah|Kota Tegal|52111|Approximate||https://maps.app.goo.gl/xs6gj6qnHhLSyz6i8
Jawa Tengah|RANK Sports Semarang, Jawa Tengah|Kota Semarang|50111|Approximate||https://maps.app.goo.gl/rzWFFNa6NL7LYCfR7
Jawa Tengah|RANK Sports Cilacap, Jawa Tengah|Cilacap|53211|Approximate||https://maps.app.goo.gl/Ye3xRxBoJBrDvFTf6
Jawa Tengah|RANK Sports Purwokerto, Jawa Tengah|Purwokerto|53111|Approximate||https://maps.app.goo.gl/YKJYwkxz8Sf6UcPZ6
Jawa Tengah|RANK Sports Kebumen, Jawa Tengah|Kebumen|54311|Approximate||https://maps.app.goo.gl/3h9MhEWTPL8zLSx69
Jawa Tengah|RANK Sports Magelang, Jawa Tengah|Kota Magelang|56111|Approximate||https://maps.app.goo.gl/Wd86EBQpPZye78fx9
Jawa Tengah|RANK Sports Kudus, Jawa Tengah|Kudus|59311|Approximate||https://maps.app.goo.gl/XChpQEYHkZn7FAYA8
Jawa Tengah|RANK Sports Surakarta (Solo), Jawa Tengah|Kota Surakarta|57111|Approximate||https://maps.app.goo.gl/G6bTPKD8Nv3W7VJo7
Jawa Tengah|RANK Sports Salatiga, Jawa Tengah|Kota Salatiga|50711|Approximate||https://maps.app.goo.gl/Jed2nFNFoMDMeuEY6
Jawa Tengah|RANK Sports Jepara, Jawa Tengah|Jepara|59411|Approximate||https://maps.app.goo.gl/NqJppP9JsWd4naQK8
Jawa Timur|RANK Sports Rungkut Surabaya, Jawa Timur|Rungkut|60293|Approximate||https://maps.app.goo.gl/BFNUiY8VDDjv6wAU6
Jawa Timur|RANK Sports Surabaya Kota, Jawa Timur|Genteng, Surabaya|60271|Approximate||https://maps.app.goo.gl/f8bbs3rjQ1zF26sd6
Jawa Timur|RANK Sports Malang, Jawa Timur|Klojen, Kota Malang|65111|Approximate||https://maps.app.goo.gl/sfS2Hm3JpHSD8eFn6
Jawa Timur|RANK Sports Tulungagung, Jawa Timur|Tulungagung|66211|Approximate||https://maps.app.goo.gl/fXkEigesAwWnqxQv6
Jawa Timur|RANK Sports Kediri, Jawa Timur|Kota Kediri|64121|Approximate||https://maps.app.goo.gl/9mJGnZCKo65WuH7a7
Jawa Timur|RANK Sports Mojokerto, Jawa Timur|Kota Mojokerto|61311|Approximate||https://maps.app.goo.gl/8uX5KUMsRcNEjTWdA
Jawa Timur|RANK Sports Blitar, Jawa Timur|Kota Blitar|66111|Approximate||https://maps.app.goo.gl/Nfp8efv7PGxo6Ggq6
Jawa Timur|RANK Sports Sidoarjo, Jawa Timur|Sidoarjo|61211|Approximate||https://maps.app.goo.gl/pTe3zq4VyZpiZGfu8
Jawa Timur|RANK Sports Madiun, Jawa Timur|Kota Madiun|63111|Approximate||https://maps.app.goo.gl/ZaEMvTPepPrUdZxu7
Jawa Timur|RANK Sports Jombang, Jawa Timur|Jombang|61411|Approximate||https://maps.app.goo.gl/URWEJzRt3MvnEpQN6
Jawa Timur|RANK Sports Gresik, Jawa Timur|Gresik|61111|Approximate||https://maps.app.goo.gl/StGbvnr6zhH7zai49
Jawa Timur|RANK Sports Nganjuk, Jawa Timur|Nganjuk|64411|Approximate||https://maps.app.goo.gl/wXsvWdrceKwKwrAh6
Jawa Timur|RANK Sports Tuban, Jawa Timur|Tuban|62311|Approximate||https://maps.app.goo.gl/zcvCo8xxb5mL4Jay5
Jawa Timur|RANK Sports Bojonegoro, Jawa Timur|Bojonegoro|62111|Approximate||https://maps.app.goo.gl/RgHyeM87ymiEpZbH9
Jawa Timur|RANK Sports Pasuruan, Jawa Timur|Kota Pasuruan|67111|Approximate||https://maps.app.goo.gl/QjtCbCLrVoJ428bd9
Jawa Timur|RANK Sports Lumajang, Jawa Timur|Lumajang|67311|Approximate||https://maps.app.goo.gl/rjpQ6MTUmSwvHAfu8
Jawa Timur|RANK Sports Probolinggo, Jawa Timur|Kota Probolinggo|67211|Approximate||https://maps.app.goo.gl/ZvDLJ26RenFj5o9x6
Jawa Timur|RANK Sports Jember, Jawa Timur|Jember|68111|Approximate||https://maps.app.goo.gl/Jt5FNqXVWT1QMoCW6
Jawa Timur|RANK Sports Bondowoso, Jawa Timur|Bondowoso|68211|Approximate||https://maps.app.goo.gl/QVsVHgEWCwrQE3yF7
Jawa Timur|RANK Sports Banyuwangi, Jawa Timur|Banyuwangi|68411|Approximate||https://maps.app.goo.gl/TLgdHvzya7dLYdcY8
Jawa Timur|RANK Sports Lamongan, Jawa Timur|Lamongan|62211|Approximate||https://maps.app.goo.gl/JYLGtDNraKr4L4ti9
Jawa Timur|RANK Sports Ponorogo, Jawa Timur|Ponorogo|63411|Approximate||https://maps.app.goo.gl/KVxnoAwK1zPELCBx5
Bali|RANK Sports Denpasar, Bali|Kota Denpasar|80111|Approximate||https://maps.app.goo.gl/P46DhtCoFmfqts1y5
Bali|RANK Sports Buleleng (Singaraja), Bali|Buleleng|81111|Approximate||https://maps.app.goo.gl/EzfUviiQTC6xAdV28
Bali|RANK Sports Gianyar, Bali|Gianyar|80511|Approximate||https://maps.app.goo.gl/Eyb1CzuhAbJ5tsii6
Kalimantan Barat|RANK Sports Pontianak, Kalbar|Kota Pontianak|78111|Approximate||https://maps.app.goo.gl/i72TE3D3grzKBrEu6
Kalimantan Barat|RANK Sports Singkawang, Kalbar|Kota Singkawang|79111|Approximate||https://maps.app.goo.gl/McfFSt3mMuFkrW7b8
Lampung|RANK Sports Bandar Lampung, Lampung|Kota Bandar Lampung|35111|Approximate||https://maps.app.goo.gl/G18PAuCsxP4pgEjv8
Lampung|RANK Sports Kota Metro, Lampung|Kota Metro|34111|Approximate||https://maps.app.goo.gl/agnkV88M2vid8Bnt9
Lampung|RANK Sports Pringsewu, Lampung|Pringsewu|35373|Approximate||https://maps.app.goo.gl/rTVgApfsMT69kwz4A
Sumatera Selatan|RANK Sports Palembang, Sumatera Selatan|Kota Palembang|30111|Approximate||https://maps.app.goo.gl/tmGxZ2KLDQ2X5ZKg8
Sumatera Selatan|RANK Sports Muara Enim, Sumatera Selatan|Muara Enim|31311|Approximate||https://maps.app.goo.gl/kiCnkeYtn3Pn7jLA8
Sumatera Selatan|RANK Sports Batu Raja, Sumatera Selatan|Baturaja|32111|Approximate||https://maps.app.goo.gl/EBUEqb7PmCW5mg9r8
Sumatera Selatan|RANK Sports Kayu Agung, Sumatera Selatan|Kayu Agung|30611|Approximate||https://maps.app.goo.gl/6q2C6BuRfLYZjquh6
Sumatera Selatan|RANK Sports Lahat, Sumatera Selatan|Lahat|31411|Approximate||https://maps.app.goo.gl/hk7JkJu3Kw93RXA37
Sumatera Selatan|RANK Sports Prabumulih, Sumatera Selatan|Kota Prabumulih|31111|Approximate||https://maps.app.goo.gl/s5VySow6Z63w3zpt6
Sumatera Barat|RANK Sports Padang, Sumatera Barat|Kota Padang|25111|Approximate||https://maps.app.goo.gl/jq7zW31U86H4Mvi29
Sumatera Barat|RANK Sports Bukittinggi, Sumatera Barat|Kota Bukittinggi|26111|Approximate||https://maps.app.goo.gl/Wz9JDjXy5vZRvFL29
Sumatera Barat|RANK Sports Payakumbuh, Sumatera Barat|Kota Payakumbuh|26211|Approximate||https://maps.app.goo.gl/Tm7DfrWchBi3CnF48
Sumatera Utara|RANK Sports Medan, Sumatera Utara|Kota Medan|20111|Approximate||https://maps.app.goo.gl/kcGeGCePjrJGhotw7
Sumatera Utara|RANK Sports Tebing Tinggi, Sumatera Utara|Kota Tebing Tinggi|20611|Approximate||https://maps.app.goo.gl/t9EVLddQNEFMw3MeA
Sumatera Utara|RANK Sports Pematang Siantar, Sumatera Utara|Kota Pematang Siantar|21111|Approximate||https://maps.app.goo.gl/FybgHaDoKpzWyMCw7
Sumatera Utara|RANK Sports Binjai, Sumatera Utara|Rambung Barat, Binjai Selatan|20742|Verified (full address)||https://maps.app.goo.gl/PJBScj5hi8zC5UCJ8
CSV;

    public function run(): void
    {
        foreach (explode("\n", self::CSV) as $baris) {
            [, $nama, $area, $kodePos, , $catatan, $peta] = explode('|', $baris);

            $isi = ["Area: {$area}"];

            if ($catatan !== '') {
                $isi[] = "Catatan: {$catatan}";
            }

            $supplier = Supplier::firstOrNew(['name' => $nama]);
            $supplier->platform = 'shopee';
            $supplier->whatsapp = $supplier->whatsapp ?: self::WHATSAPP_BAWAAN;
            $supplier->origin_postal_code = $kodePos;
            $supplier->shop_url = $peta;
            $supplier->notes = implode("\n", $isi);
            $supplier->save();
        }
    }
}
