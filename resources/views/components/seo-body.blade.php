@php($gtmId = \App\Models\PengaturanSitus::ambil('gtm_id'))

@if ($gtmId !== '')
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
