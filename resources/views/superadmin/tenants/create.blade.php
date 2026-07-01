<x-layouts::superadmin active-page="tenants.create" judul="Tambah Tenant">
    <div class="mb-6">
        <h1 class="font-display text-xl font-semibold text-ink">Tambah Tenant Baru</h1>
        <p class="text-sm text-ink-soft mt-0.5">Isi data bisnis dan PIC klien, lalu generate link undangan owner.</p>
    </div>

    <form method="POST" action="{{ route('superadmin.tenants.store') }}" class="max-w-3xl space-y-6">
        @csrf

        <x-card>
            <div class="text-xs font-bold uppercase tracking-wide text-plum mb-4">Info Bisnis</div>

            <div class="space-y-4">
                <div>
                    <x-input label="Nama Bisnis" name="nama_bisnis" placeholder="Misal Arena Sport Center" :value="old('nama_bisnis')" />
                    @error('nama_bisnis') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>

                <div x-data="{ subdomain: @js(old('subdomain', '')) }">
                    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Subdomain</label>
                    <div class="flex rounded-lg border border-cream-deep bg-cream overflow-hidden focus-within:border-green focus-within:ring-1 focus-within:ring-green transition-colors">
                        <input
                            type="text"
                            name="subdomain"
                            :value="subdomain"
                            x-on:input="subdomain = $event.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '')"
                            placeholder="arena-sport"
                            class="flex-1 min-w-0 bg-transparent px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:outline-none"
                        />
                        <span class="flex items-center px-3 text-sm text-ink-soft bg-cream-dark border-l border-cream-deep whitespace-nowrap">
                            .greendeahan.com
                        </span>
                    </div>
                    <p class="text-xs text-ink-soft mt-1">Huruf kecil, angka, dan tanda hubung saja. Otomatis dirapikan saat kamu mengetik.</p>
                    @error('subdomain') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>

                <div x-data="{ paket: @js(old('paket', 'pro')) }">
                    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-2">Paket</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="rounded-lg border-2 p-4 cursor-pointer transition-colors"
                               :class="paket === 'basic' ? 'border-green bg-green-pale' : 'border-cream-deep bg-white hover:border-sand'">
                            <input type="radio" name="paket" value="basic" x-model="paket" class="sr-only">
                            <div class="font-display font-semibold text-ink mb-1">Basic</div>
                            <div class="text-lg font-bold text-green mb-1">
                                Rp299rb<span class="text-xs font-normal text-ink-soft">/bulan</span>
                            </div>
                            <p class="text-xs text-ink-soft">Booking online dasar, 1 lapangan, notifikasi WhatsApp manual.</p>
                        </label>

                        <label class="rounded-lg border-2 p-4 cursor-pointer transition-colors"
                               :class="paket === 'pro' ? 'border-green bg-green-pale' : 'border-cream-deep bg-white hover:border-sand'">
                            <input type="radio" name="paket" value="pro" x-model="paket" class="sr-only">
                            <div class="font-display font-semibold text-ink mb-1">Pro</div>
                            <div class="text-lg font-bold text-green mb-1">
                                Rp599rb<span class="text-xs font-normal text-ink-soft">/bulan</span>
                            </div>
                            <p class="text-xs text-ink-soft">Sampai 3 lapangan, pembayaran online, kode promo, laporan pendapatan.</p>
                        </label>

                        <label class="rounded-lg border-2 p-4 cursor-pointer transition-colors"
                               :class="paket === 'premium' ? 'border-green bg-green-pale' : 'border-cream-deep bg-white hover:border-sand'">
                            <input type="radio" name="paket" value="premium" x-model="paket" class="sr-only">
                            <div class="font-display font-semibold text-ink mb-1">Premium</div>
                            <div class="text-lg font-bold text-green mb-1">
                                Rp1.199rb<span class="text-xs font-normal text-ink-soft">/bulan</span>
                            </div>
                            <p class="text-xs text-ink-soft">Lapangan tanpa batas, multi cabang, membership, staf, reminder otomatis.</p>
                        </label>
                    </div>
                    @error('paket') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input label="Kota" name="kota" placeholder="Misal Jakarta" :value="old('kota')" />
                    <p class="text-xs text-ink-soft mt-1">Dipakai untuk cabang default (Cabang Utama) tenant ini.</p>
                    @error('kota') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="text-xs font-bold uppercase tracking-wide text-plum mb-4">Info PIC (Pemilik Bisnis)</div>

            <div class="space-y-4">
                <div>
                    <x-input label="Nama PIC" name="nama_pic" placeholder="Nama pemilik bisnis" :value="old('nama_pic')" />
                    @error('nama_pic') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input label="Email PIC" name="email_pic" type="email" placeholder="pemilik@bisnis.com" :value="old('email_pic')" />
                    <p class="text-xs text-ink-soft mt-1">Link undangan untuk jadi owner akan dikirim ke email ini.</p>
                    @error('email_pic') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input label="WhatsApp PIC" name="whatsapp_pic" type="tel" placeholder="08xxxxxxxxxx" :value="old('whatsapp_pic')" />
                    @error('whatsapp_pic') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-card>

        <div class="flex gap-2">
            <x-button type="submit">Buat Tenant dan Generate Invitation Link</x-button>
            <a href="{{ route('superadmin.tenants') }}" class="inline-flex items-center text-sm text-ink-soft underline">Batal</a>
        </div>
    </form>
</x-layouts::superadmin>
