<x-layouts::superadmin active-page="seo" judul="SEO dan Analytics">
    <div class="mb-6">
        <h1 class="font-display text-xl font-semibold text-ink">SEO dan Analytics</h1>
        <p class="text-sm text-ink-soft mt-0.5">Berlaku untuk situs korporat (beranda, konsep, galeri, blog, harga, kontak, daftar). Halaman booking tenant tidak ikut dilacak.</p>
    </div>

    <details class="max-w-2xl mb-5 group text-sm text-ink-mid">
        <summary class="cursor-pointer select-none text-xs font-semibold text-ink-soft hover:text-ink">Panduan langkah demi langkah</summary>

        <div class="mt-3 space-y-4 border-l-2 border-cream-deep pl-4">
            <div>
                <div class="text-xs font-bold text-ink mb-1">Google Analytics 4</div>
                <ol class="list-decimal ps-5 space-y-1 text-xs text-ink-soft">
                    <li>Buka <a href="https://analytics.google.com" target="_blank" rel="noopener" class="underline">analytics.google.com</a> dan masuk dengan akun Google.</li>
                    <li>Buat properti baru (Admin, Create, Property), lalu isi nama dan zona waktu.</li>
                    <li>Pilih platform Web, masukkan domain situs korporat, dan beri nama stream.</li>
                    <li>Setelah stream jadi, salin Measurement ID yang berawalan <code>G-</code> di pojok kanan atas.</li>
                    <li>Tempel di kolom Measurement ID di bawah, lalu simpan.</li>
                    <li>Cek di GA4 bagian Reports, Realtime sambil membuka situs di tab lain. Kunjunganmu akan muncul dalam beberapa detik.</li>
                </ol>
            </div>

            <div>
                <div class="text-xs font-bold text-ink mb-1">Google Search Console</div>
                <ol class="list-decimal ps-5 space-y-1 text-xs text-ink-soft">
                    <li>Buka <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="underline">search.google.com/search-console</a>, klik Add property.</li>
                    <li>Pilih tipe URL prefix dan isi alamat lengkap situs, misalnya <code>https://greendeahan.com</code>.</li>
                    <li>Pada metode verifikasi, pilih HTML tag. Salin hanya isi <code>content</code> dari tag meta yang muncul.</li>
                    <li>Tempel di kolom Kode Verifikasi di bawah dan simpan, lalu kembali ke Search Console dan klik Verify.</li>
                    <li>Opsional: di menu Sitemaps, kirim alamat sitemap kalau sudah tersedia.</li>
                </ol>
            </div>

            <div>
                <div class="text-xs font-bold text-ink mb-1">SEO dasar</div>
                <ol class="list-decimal ps-5 space-y-1 text-xs text-ink-soft">
                    <li>Isi nama situs dan deskripsi default dengan kalimat yang menjelaskan bisnis, sekitar 120 sampai 160 karakter.</li>
                    <li>Upload gambar share di halaman Media, salin URL-nya, lalu tempel di kolom Gambar Share.</li>
                    <li>Biarkan opsi indeks mesin pencari aktif saat situs siap tampil. Matikan hanya saat masih tahap pengembangan.</li>
                    <li>Uji tampilan share dengan menempelkan link situs di WhatsApp atau chat pribadi.</li>
                </ol>
            </div>

            <p class="text-xs text-ink-soft">Data di GA4 biasanya baru lengkap setelah 24 sampai 48 jam, dan Search Console butuh beberapa hari untuk mulai menampilkan data.</p>
        </div>
    </details>

    <form method="POST" action="{{ route('superadmin.seo.update') }}" class="space-y-5 max-w-2xl">
        @csrf
        @method('PUT')

        <x-card>
            <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">Google Analytics 4</div>
            <label for="ga4_measurement_id" class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Measurement ID</label>
            <input id="ga4_measurement_id" name="ga4_measurement_id" type="text" placeholder="G-XXXXXXXXXX"
                   value="{{ old('ga4_measurement_id', $pengaturan['ga4_measurement_id']) }}"
                   class="w-full rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
            <x-input-error :messages="$errors->get('ga4_measurement_id')" class="mt-1" />
            <p class="text-xs text-ink-soft mt-1.5">Ada di GA4: Admin, Data Streams, pilih stream web, lalu salin Measurement ID. Kosongkan untuk mematikan pelacakan.</p>
        </x-card>

        <x-card>
            <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">Google Search Console</div>
            <label for="gsc_verifikasi" class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Kode Verifikasi</label>
            <input id="gsc_verifikasi" name="gsc_verifikasi" type="text"
                   value="{{ old('gsc_verifikasi', $pengaturan['gsc_verifikasi']) }}"
                   class="w-full rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
            <x-input-error :messages="$errors->get('gsc_verifikasi')" class="mt-1" />
            <p class="text-xs text-ink-soft mt-1.5">Pilih metode "HTML tag" di Search Console, lalu salin nilai <code>content</code> dari tag meta yang diberikan.</p>
        </x-card>

        <x-card>
            <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">SEO Dasar</div>

            <label for="nama_situs" class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Nama Situs</label>
            <input id="nama_situs" name="nama_situs" type="text" required
                   value="{{ old('nama_situs', $pengaturan['nama_situs']) }}"
                   class="w-full rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink mb-1">
            <x-input-error :messages="$errors->get('nama_situs')" class="mb-3" />

            <label for="meta_deskripsi" class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1 mt-3">Deskripsi Default</label>
            <textarea id="meta_deskripsi" name="meta_deskripsi" rows="3" required
                      class="w-full rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">{{ old('meta_deskripsi', $pengaturan['meta_deskripsi']) }}</textarea>
            <x-input-error :messages="$errors->get('meta_deskripsi')" class="mt-1" />
            <p class="text-xs text-ink-soft mt-1">Dipakai di hasil pencarian Google. Ideal 120 sampai 160 karakter. Halaman blog memakai ringkasan artikelnya sendiri.</p>

            <label for="og_image_url" class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1 mt-3">Gambar Share (Open Graph)</label>
            <input id="og_image_url" name="og_image_url" type="url" placeholder="https://..."
                   value="{{ old('og_image_url', $pengaturan['og_image_url']) }}"
                   class="w-full rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
            <x-input-error :messages="$errors->get('og_image_url')" class="mt-1" />
            <p class="text-xs text-ink-soft mt-1">Gambar yang muncul saat link dibagikan ke WhatsApp atau sosial media. Ukuran ideal 1200 x 630. Salin URL dari halaman Media.</p>

            <label class="inline-flex items-center gap-2 mt-4">
                <input type="checkbox" name="indeks_mesin_pencari" value="1" class="rounded border-cream-deep text-green shadow-sm focus:ring-green"
                       @checked(old() ? old('indeks_mesin_pencari') === '1' : $pengaturan['indeks_mesin_pencari'] === '1')>
                <span class="text-sm text-ink-mid">Izinkan mesin pencari mengindeks situs</span>
            </label>
        </x-card>

        <x-button type="submit">Simpan Pengaturan</x-button>
    </form>
</x-layouts::superadmin>
