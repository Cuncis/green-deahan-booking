<?php

/*
|--------------------------------------------------------------------------
| Monitoring error (Log Error dan peringatan error)
|--------------------------------------------------------------------------
|
| Halaman Log Error (superadmin) membaca file log Laravel. Peringatan error
| mengirim notifikasi lonceng ke semua superadmin dan email ke penerima yang
| dipilih di Pengaturan > Email & Notifikasi, paling banyak satu kali per jam
| untuk error yang sama. Email lewat mailer yang aktif (lihat MAIL_* di .env).
|
*/

return [

    /*
     * Folder file log Laravel (laravel.log atau laravel-YYYY-MM-DD.log).
     */
    'log_folder' => storage_path('logs'),

    /*
     * Batas data log yang dibaca per tampilan, dari ujung file (yang terbaru).
     * File log produksi bisa sangat besar, jadi halaman hanya membaca bagian
     * akhirnya. Isi dengan angka megabyte.
     */
    'log_maks_byte' => (int) env('LOG_ERROR_MAKS_MB', 8) * 1024 * 1024,

    /*
     * Saklar peringatan error (notifikasi lonceng dan email).
     */
    'peringatan_aktif' => (bool) env('PERINGATAN_ERROR_AKTIF', true),

    /*
     * Jeda minimum (menit) sebelum error yang sama dikabarkan lagi.
     */
    'peringatan_jeda_menit' => (int) env('PERINGATAN_ERROR_JEDA_MENIT', 60),

];
