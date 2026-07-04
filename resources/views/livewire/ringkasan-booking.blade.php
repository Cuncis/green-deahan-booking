<div>
    @if ($tenant->punyaFitur('kode_promo'))
        <x-promo-input model="kodePromo" :status="$promoValid" action="cekPromo" />
    @endif

    <div class="mb-5">
        <div class="text-xs font-bold uppercase tracking-wide text-green mb-2">Metode Pembayaran</div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mb-1.5 text-xs font-medium text-ink-soft">
            <span class="inline-flex items-center gap-1.5">
                <x-icon name="qris" size="15" class="text-ink-soft" />
                QRIS
            </span>
            <span class="inline-flex items-center gap-1.5">
                <x-icon name="wallet" size="15" class="text-ink-soft" />
                E-Wallet
            </span>
            <span class="inline-flex items-center gap-1.5">
                <x-icon name="bank-transfer" size="15" class="text-ink-soft" />
                Transfer VA
            </span>
        </div>
        <p class="text-xs text-ink-soft">Kamu akan diarahkan ke halaman pembayaran Midtrans untuk memilih metode dan menyelesaikan pembayaran.</p>
    </div>

    @if ($tenant->punyaFitur('dp_pembayaran'))
        <x-payment-option-toggle :harga="$harga" :tipe-pembayaran="$tipePembayaran" action="pilihTipePembayaran" />
    @endif
</div>
