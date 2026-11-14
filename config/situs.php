<?php

/*
|--------------------------------------------------------------------------
| Situs korporat (greendeahan.com)
|--------------------------------------------------------------------------
*/

return [

    /*
     * false = halaman /sistem-booking masih dalam pengembangan: tetap bisa
     * dibuka lewat alamat langsung, tapi tidak ada di menu, footer, dan
     * sitemap, dan diberi noindex. Buka untuk umum: SISTEM_BOOKING_PUBLIK=true.
     * Toko punya saklar sendiri di config/toko.php (TOKO_PUBLIK).
     */
    'sistem_booking_publik' => (bool) env('SISTEM_BOOKING_PUBLIK', false),

];
