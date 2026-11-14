<?php

/*
|--------------------------------------------------------------------------
| Toko online (dropship) di situs korporat
|--------------------------------------------------------------------------
*/

return [

    /*
     * false = toko belum dibuka untuk umum: halamannya tetap bisa dibuka
     * lewat alamat langsung (/toko) untuk uji internal, tapi menu "Toko" dan
     * ikon keranjang disembunyikan di situs, dan semua halaman toko diberi
     * noindex supaya tidak muncul di Google. Buka untuk umum: TOKO_PUBLIK=true.
     */
    'publik' => (bool) env('TOKO_PUBLIK', false),

    /*
     * Ongkir tarif flat untuk versi pertama (rupiah). Nanti bisa diganti
     * perhitungan ongkir per kota/berat.
     */
    'ongkir_flat' => (int) env('TOKO_ONGKIR_FLAT', 15000),

    /*
     * Pesanan yang belum dibayar setelah sekian jam otomatis kedaluwarsa
     * (command toko:kedaluwarsakan-pesanan, jalan tiap jam).
     */
    'jam_kedaluwarsa' => (int) env('TOKO_JAM_KEDALUWARSA', 24),

    /*
     * Penerima email "Pesanan baru perlu dibeli ke supplier".
     */
    'email_admin' => env('TOKO_EMAIL_ADMIN', env('ADMIN_EMAIL', 'hello@greendeahan.com')),

    /*
     * Batas jumlah per produk di keranjang. Material lapangan dijual per m²,
     * jadi batasnya besar.
     */
    'maks_qty_per_produk' => 999,

];
