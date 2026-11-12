<?php

/*
|--------------------------------------------------------------------------
| Toko online (dropship) di situs korporat
|--------------------------------------------------------------------------
*/

return [

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

    'maks_qty_per_produk' => 20,

];
