<?php

/*
|--------------------------------------------------------------------------
| Biteship (ongkos kirim toko online)
|--------------------------------------------------------------------------
|
| Dipakai untuk menghitung ongkir di checkout toko (POST /v1/rates/couriers).
| Pengiriman barang dropship dilakukan supplier (Shopee), jadi resi tetap
| diisi manual di admin dan tidak ada order yang dibuat di Biteship.
|
| Sandbox: aktifkan "Testing Mode" di dashboard Biteship
| (https://dashboard.biteship.com/integrations), buat API key (awalan
| "biteship_test."), lalu isi BITESHIP_API_KEY di .env. Selama BITESHIP_ENV
| bukan "production", key live ("biteship_live.") ditolak. Tanpa API key,
| checkout memakai ongkir flat (TOKO_ONGKIR_FLAT).
|
*/

return [

    'env' => env('BITESHIP_ENV', 'sandbox'),

    'api_key' => env('BITESHIP_API_KEY'),

    'base_url' => rtrim((string) env('BITESHIP_BASE_URL', 'https://api.biteship.com'), '/'),

    /*
     * Kurir yang ditawarkan ke pelanggan (kode kurir Biteship, dipisah koma):
     * jne, jnt (J&T), sicepat, anteraja.
     */
    'couriers' => env('BITESHIP_COURIERS', 'jne,jnt,sicepat,anteraja'),

    /*
     * Kode pos asal pengiriman cadangan, dipakai kalau supplier produk belum
     * punya kode pos asal sendiri (diisi di Admin > Toko > Supplier).
     */
    'origin_postal_code' => env('BITESHIP_ORIGIN_POSTAL_CODE'),

    /*
     * Cadangan ongkir (persen) yang ditambahkan ke tiap paket. Supplier seperti
     * RANK Sports mengirim dari cabang mana pun yang punya stok, jadi asal
     * paket bisa lebih jauh dari perkiraan. Cadangan menutup selisihnya.
     * Dibulatkan ke atas per Rp500. Isi 0 untuk mematikan.
     */
    'cadangan_persen' => (float) env('BITESHIP_CADANGAN_PERSEN', 10),

    /*
     * Kalau Biteship gagal (gangguan, kunci salah, kode pos asal belum diisi),
     * checkout menawarkan "ongkir tetap" sebesar TOKO_ONGKIR_FLAT supaya
     * pembeli tetap bisa membayar. Server hanya menerimanya selama Biteship
     * memang masih gagal. Isi false untuk menolak checkout saat Biteship gagal.
     */
    'ongkir_darurat' => (bool) env('BITESHIP_ONGKIR_DARURAT', true),

    /*
     * Berat (gram) per barang kalau produk belum diisi beratnya. Tarif
     * dihitung dari berat, jadi isi berat tiap produk dengan benar.
     */
    'berat_bawaan' => (int) env('BITESHIP_BERAT_BAWAAN', 500),

    /*
     * Hasil tarif yang sama (asal, tujuan, isi keranjang) disimpan sebentar
     * supaya tidak memanggil API berulang kali.
     */
    'cache_menit' => (int) env('BITESHIP_CACHE_MENIT', 10),

    /*
     * Biaya per permintaan tarif (rupiah), hanya untuk perkiraan biaya di dashboard superadmin.
     */
    'tarif_per_permintaan' => (int) env('BITESHIP_TARIF_PER_PERMINTAAN', 5),

    'timeout' => (int) env('BITESHIP_TIMEOUT', 15),

];
