<div>
    @if ($tenant->punyaFitur('kode_promo'))
        <x-promo-input model="kodePromo" :status="$promoValid" action="cekPromo" />
    @endif

    <x-payment-methods :selected="$metodePembayaran" action="pilihMetodePembayaran" />

    @if ($tenant->punyaFitur('dp_pembayaran'))
        <x-payment-option-toggle :harga="$harga" :tipe-pembayaran="$tipePembayaran" action="pilihTipePembayaran" />
    @endif
</div>
