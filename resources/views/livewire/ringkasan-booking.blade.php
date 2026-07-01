<div>
    @if ($tenant->punyaFitur('kode_promo'))
        <x-promo-input model="kodePromo" :status="$promoValid" action="cekPromo" />
    @endif

    @if ($tenant->punyaFitur('pembayaran_online'))
        <x-payment-methods />
    @endif

    @if ($tenant->punyaFitur('dp_pembayaran'))
        <x-payment-option-toggle :harga="$harga" :tipe-pembayaran="$tipePembayaran" action="pilihTipePembayaran" />
    @endif

    @if (! $tenant->punyaFitur('pembayaran_online'))
        <x-manual-transfer-info :tenant="$tenant" />
    @endif
</div>
