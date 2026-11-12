<?php

/*
|--------------------------------------------------------------------------
| Mayar.id (payment gateway)
|--------------------------------------------------------------------------
|
| Satu akun Mayar dipakai untuk booking lapangan, langganan tenant, dan
| toko online. Ganti sandbox ke produksi HANYA lewat .env:
|
|   MAYAR_ENV=production
|   MAYAR_BASE_URL=https://api.mayar.id
|   MAYAR_API_KEY=<key produksi>
|
| Sandbox: buat API key di dashboard sandbox Mayar (https://web.mayar.club,
| menu Integration > API Key). Dokumentasi Mayar terbaru menyebut sandbox
| di api.mayar.io / web.mayar.io; kalau key kamu dari sana, isi
| MAYAR_BASE_URL=https://api.mayar.io.
|
*/

$env = env('MAYAR_ENV', env('MAYAR_IS_PRODUCTION', false) ? 'production' : 'sandbox');

return [

    'env' => $env,

    'api_key' => env('MAYAR_API_KEY'),

    'base_url' => rtrim((string) env('MAYAR_BASE_URL', $env === 'production' ? 'https://api.mayar.id' : 'https://api.mayar.club'), '/'),

    /*
     * Rahasia pilihan sendiri, dipasang sebagai ?token=... di URL webhook
     * yang didaftarkan di dashboard Mayar. Mayar tidak menandatangani
     * isi webhook, jadi ini lapisan pertama; lapisan kedua adalah cek
     * ulang status pembayaran langsung ke API Mayar.
     */
    'webhook_token' => env('MAYAR_WEBHOOK_TOKEN'),

    'timeout' => (int) env('MAYAR_TIMEOUT', 15),

];
