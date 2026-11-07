@props(['deskripsi' => null, 'image' => null, 'tipe' => 'website'])

@php
    $seo = \App\Models\PengaturanSitus::semua();
    $deskripsi = $deskripsi ?: $seo['meta_deskripsi'];
    $image = $image ?: $seo['og_image_url'];
@endphp

<meta name="description" content="{{ $deskripsi }}">
<link rel="canonical" href="{{ url()->current() }}">

@if ($seo['indeks_mesin_pencari'] !== '1')
    <meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:site_name" content="{{ $seo['nama_situs'] }}">
<meta property="og:type" content="{{ $tipe }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:description" content="{{ $deskripsi }}">
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:image" content="{{ $image }}">
@endif

@if ($seo['gsc_verifikasi'] !== '')
    <meta name="google-site-verification" content="{{ $seo['gsc_verifikasi'] }}">
@endif

@if ($seo['ga4_measurement_id'] !== '')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo['ga4_measurement_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @js($seo['ga4_measurement_id']));
    </script>
@endif

@if ($seo['gtm_id'] !== '')
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
    </script>
    <script async src="https://www.googletagmanager.com/gtm.js?id={{ $seo['gtm_id'] }}"></script>
@endif
