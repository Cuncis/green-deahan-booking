<?php

/*
|--------------------------------------------------------------------------
| RajaOngkir (ongkos kirim toko online, penyedia utama)
|--------------------------------------------------------------------------
|
| RajaOngkir (Komerce) gratis sampai batas harian (100 permintaan per hari),
| jadi dipakai lebih dulu. Kalau batas harian habis atau RajaOngkir gagal,
| tarif diambil dari Biteship (berbayar per permintaan, lihat config/biteship.php).
| Tanpa RAJAONGKIR_API_KEY, hanya Biteship yang dipakai.
|
| Dokumentasi: https://rajaongkir.com/docs
|
| Satu permintaan API dihitung satu: pencarian lokasi (kode pos ke id lokasi)
| dan hitung tarif. Hasil keduanya disimpan di cache supaya jarang memakai kuota.
|
*/

return [

    'api_key' => env('RAJAONGKIR_API_KEY'),

    'base_url' => rtrim((string) env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'), '/'),

    /*
     * Kurir yang ditawarkan, dipisah titik dua (format RajaOngkir): jne:jnt:sicepat:anteraja.
     */
    'couriers' => env('RAJAONGKIR_COURIERS', 'jne:jnt:sicepat:anteraja'),

    /*
     * Batas permintaan per hari (WIB). Sisa kecil disisakan supaya hitungan kita yang
     * sedikit meleset tidak membuat RajaOngkir menolak lebih dulu. Ini nilai awal:
     * superadmin bisa mengubahnya di Toko Online > Pengaturan Ongkir (yang diisi di
     * sana menimpa nilai .env).
     */
    'batas_harian' => (int) env('RAJAONGKIR_BATAS_HARIAN', 100),

    'sisa_aman' => (int) env('RAJAONGKIR_SISA_AMAN', 2),

    'cache_menit' => (int) env('RAJAONGKIR_CACHE_MENIT', 10),

    /*
     * Id lokasi dari kode pos jarang berubah, jadi disimpan lama.
     */
    'cache_lokasi_hari' => (int) env('RAJAONGKIR_CACHE_LOKASI_HARI', 30),

    'timeout' => (int) env('RAJAONGKIR_TIMEOUT', 15),

];
