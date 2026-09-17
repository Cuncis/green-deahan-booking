{{--
    Kalkulator Perencanaan Sport Center. Mesin hitung (PLANNER_CONFIG +
    PlannerCalculator) murni JS berbasis aturan (bukan AI), dipisah dari
    state UI (plannerApp) supaya angka-angka (ukuran lapangan, kebutuhan
    ruang fasilitas, kisaran biaya, bobot skor) bisa diubah kontraktor
    tanpa membongkar markup. Seluruh script dibungkus tag verbatim Blade
    karena isinya JS murni (objek/fungsi dengan kurung kurawal berpasangan)
    yang tidak boleh disentuh compiler Blade.
--}}
@php
    $sportsList = [
        ['key' => 'futsal', 'label' => 'Futsal', 'icon' => 'futsal-goal'],
        ['key' => 'miniSoccer', 'label' => 'Mini Soccer', 'icon' => 'soccer-ball'],
        ['key' => 'padel', 'label' => 'Padel', 'icon' => 'padel-racket'],
        ['key' => 'badminton', 'label' => 'Badminton', 'icon' => 'shuttlecock'],
        ['key' => 'basketball', 'label' => 'Basketball', 'icon' => 'basketball-ball'],
        ['key' => 'lainnya', 'label' => 'Lainnya', 'icon' => 'plus'],
    ];

    $futsalVariants = [
        ['key' => 'compact', 'label' => 'Compact / Rekreasi', 'ukuran' => '15 x 30 m'],
        ['key' => 'standard', 'label' => 'Standard', 'ukuran' => '18 x 35 m'],
        ['key' => 'large', 'label' => 'Large', 'ukuran' => '20 x 40 m'],
        ['key' => 'kompetisi', 'label' => 'Kompetisi', 'ukuran' => '22 x 40 m'],
    ];

    $facilitiesList = [
        ['key' => 'parkir', 'label' => 'Area Parkir', 'icon' => 'parking'],
        ['key' => 'toilet', 'label' => 'Toilet', 'icon' => 'toilet'],
        ['key' => 'ruangGanti', 'label' => 'Ruang Ganti', 'icon' => 'shower'],
        ['key' => 'kafe', 'label' => 'Kafe', 'icon' => 'coffee-cup'],
        ['key' => 'resepsionis', 'label' => 'Resepsionis', 'icon' => 'building'],
        ['key' => 'musala', 'label' => 'Musala', 'icon' => 'prayer-room'],
        ['key' => 'gym', 'label' => 'Gym', 'icon' => 'dumbbell'],
        ['key' => 'retail', 'label' => 'Area Retail', 'icon' => 'shopping-bag'],
        ['key' => 'gudang', 'label' => 'Gudang', 'icon' => 'storage-box'],
        ['key' => 'lainnya', 'label' => 'Lainnya', 'icon' => 'plus'],
    ];

    $budgetOptions = [
        ['key' => 'di_bawah_1m', 'label' => 'Di bawah Rp 1 Miliar'],
        ['key' => 'satu_dua_m', 'label' => 'Rp 1 sampai 2 Miliar'],
        ['key' => 'dua_tiga_m', 'label' => 'Rp 2 sampai 3 Miliar'],
        ['key' => 'tiga_lima_m', 'label' => 'Rp 3 sampai 5 Miliar'],
        ['key' => 'lima_plus_m', 'label' => 'Rp 5 Miliar ke atas'],
    ];

    $landPresets = [
        ['p' => 20, 'l' => 30], ['p' => 30, 'l' => 30], ['p' => 35, 'l' => 40], ['p' => 45, 'l' => 35],
    ];
@endphp

<section class="mx-auto mb-16 max-w-5xl px-6" x-data="plannerApp()">
    <div class="reveal mb-10 text-center">
        <span class="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Kalkulator Interaktif</span>
        <h2 class="font-marketing-display text-3xl font-black text-stone-900 md:text-4xl">Coba Kalkulator Perencanaan Lapangan</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm text-stone-500">Isi ukuran lahan, budget, dan kebutuhan Anda. Kami bantu hitung konfigurasi yang mungkin cocok untuk lokasi Anda.</p>
    </div>

    <div class="reveal rounded-3xl border border-stone-200 bg-white p-5 shadow-sm md:p-8">

        <!-- Progress bar -->
        <div class="mb-8 flex items-center justify-between">
            <template x-for="(label, i) in stepLabels" :key="i">
                <div class="flex flex-1 items-center">
                    <button
                        type="button"
                        :disabled="i + 1 > step"
                        x-on:click="if (i + 1 < step) step = i + 1"
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors"
                        :class="i + 1 === step ? 'bg-brand text-white' : (i + 1 < step ? 'bg-brand-100 text-brand cursor-pointer' : 'bg-stone-100 text-stone-400')"
                        x-text="i + 1 < step ? '&#10003;' : i + 1"
                    ></button>
                    <span class="ml-2 hidden text-xs font-bold sm:inline" :class="i + 1 === step ? 'text-stone-900' : 'text-stone-400'" x-text="label"></span>
                    <div class="mx-2 h-0.5 flex-1 rounded-full" :class="i + 1 < step ? 'bg-brand-200' : 'bg-stone-100'" x-show="i < stepLabels.length - 1"></div>
                </div>
            </template>
        </div>

        <!-- Step 1: Land -->
        <div x-show="step === 1" x-cloak>
            <h3 class="font-marketing-display mb-1 text-lg font-black text-stone-900">Seberapa besar lahan Anda?</h3>
            <p class="mb-5 text-sm text-stone-500">Pilih salah satu ukuran umum, atau pilih "Lainnya" untuk masukkan ukuran sendiri.</p>

            <div class="mb-5 flex flex-wrap gap-2">
                @foreach ($landPresets as $preset)
                    <button
                        type="button"
                        x-on:click="panjang = {{ $preset['p'] }}; lebar = {{ $preset['l'] }}; landChoice = '{{ $preset['p'] }}x{{ $preset['l'] }}'"
                        class="rounded-lg border-2 px-3 py-2 text-xs font-bold transition-colors"
                        :class="landChoice === '{{ $preset['p'] }}x{{ $preset['l'] }}' ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-600 hover:border-brand hover:text-brand'"
                    >{{ $preset['p'] }} x {{ $preset['l'] }} m</button>
                @endforeach
                <button
                    type="button"
                    x-on:click="panjang = null; lebar = null; landChoice = 'lainnya'"
                    class="rounded-lg border-2 px-3 py-2 text-xs font-bold transition-colors"
                    :class="landChoice === 'lainnya' ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-600 hover:border-brand hover:text-brand'"
                >Lainnya</button>
            </div>

            <div class="mb-5 grid grid-cols-2 gap-4" x-show="landChoice === 'lainnya'" x-cloak>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Panjang (meter)</label>
                    <input type="number" min="1" x-model.number="panjang" placeholder="45"
                           class="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Lebar (meter)</label>
                    <input type="number" min="1" x-model.number="lebar" placeholder="35"
                           class="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none" />
                </div>
            </div>

            <div class="rounded-xl bg-brand-50 p-4 text-center" x-show="luas > 0">
                <p class="text-xs font-bold uppercase tracking-wide text-brand">Total Luas Lahan</p>
                <p class="font-marketing-display text-2xl font-black text-stone-900" x-text="formatM2(luas)"></p>
            </div>
        </div>

        <!-- Step 2: Budget -->
        <div x-show="step === 2" x-cloak>
            <h3 class="font-marketing-display mb-1 text-lg font-black text-stone-900">Berapa estimasi budget Anda?</h3>
            <p class="mb-5 text-sm text-stone-500">Ini estimasi awal saja, harga final tergantung hasil survey lokasi.</p>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($budgetOptions as $budget)
                    <button
                        type="button"
                        x-on:click="budgetKey = '{{ $budget['key'] }}'; budgetCustom = null"
                        class="rounded-xl border-2 px-4 py-3 text-left text-sm font-bold transition-colors"
                        :class="budgetKey === '{{ $budget['key'] }}' ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-700 hover:border-brand'"
                    >{{ $budget['label'] }}</button>
                @endforeach
            </div>

            <div class="mt-3">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Atau masukkan budget spesifik (Rp)</label>
                <input type="number" min="0" x-model.number="budgetCustom" x-on:input="budgetKey = 'custom'" placeholder="2000000000"
                       class="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                       :class="budgetKey === 'custom' ? 'border-brand' : ''" />
            </div>
        </div>

        <!-- Step 3: Sports -->
        <div x-show="step === 3" x-cloak>
            <h3 class="font-marketing-display mb-1 text-lg font-black text-stone-900">Apa yang ingin Anda bangun?</h3>
            <p class="mb-5 text-sm text-stone-500">Boleh pilih lebih dari satu jenis olahraga.</p>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($sportsList as $sport)
                    <button
                        type="button"
                        x-on:click="toggleSport('{{ $sport['key'] }}')"
                        class="flex flex-col items-center gap-2 rounded-xl border-2 px-3 py-4 text-center transition-colors"
                        :class="selectedSports.includes('{{ $sport['key'] }}') ? 'border-brand bg-brand-50' : 'border-stone-200 hover:border-brand'"
                    >
                        <x-icon name="{{ $sport['icon'] }}" size="22" class="text-brand" />
                        <span class="text-xs font-bold text-stone-700">{{ $sport['label'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="mt-5 rounded-xl border border-stone-200 p-4" x-show="selectedSports.includes('futsal')" x-cloak>
                <p class="mb-3 text-xs font-bold uppercase tracking-wide text-stone-600">Pilih konfigurasi lapangan futsal</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($futsalVariants as $variant)
                        <button
                            type="button"
                            x-on:click="futsalVariant = '{{ $variant['key'] }}'"
                            class="rounded-lg border-2 px-2 py-2.5 text-center transition-colors"
                            :class="futsalVariant === '{{ $variant['key'] }}' ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-600 hover:border-brand'"
                        >
                            <span class="block text-xs font-bold">{{ $variant['label'] }}</span>
                            <span class="block text-[11px] text-stone-400">{{ $variant['ukuran'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Step 4: Facilities -->
        <div x-show="step === 4" x-cloak>
            <h3 class="font-marketing-display mb-1 text-lg font-black text-stone-900">Apa lagi yang Anda inginkan di lokasi?</h3>
            <p class="mb-5 text-sm text-stone-500">Boleh pilih lebih dari satu, atau lewati kalau belum tahu.</p>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($facilitiesList as $facility)
                    <button
                        type="button"
                        x-on:click="toggleFacility('{{ $facility['key'] }}')"
                        class="flex flex-col items-center gap-2 rounded-xl border-2 px-3 py-4 text-center transition-colors"
                        :class="selectedFacilities.includes('{{ $facility['key'] }}') ? 'border-brand bg-brand-50' : 'border-stone-200 hover:border-brand'"
                    >
                        <x-icon name="{{ $facility['icon'] }}" size="22" class="text-brand" />
                        <span class="text-xs font-bold text-stone-700">{{ $facility['label'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="mt-5 flex items-center justify-between rounded-xl border border-stone-200 p-4" x-show="selectedFacilities.includes('parkir')" x-cloak>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-600">Perkiraan kapasitas parkir</p>
                    <p class="text-xs text-stone-400">Jumlah mobil yang ingin ditampung</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" x-on:click="parkirUnits = Math.max(1, parkirUnits - 1)" class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 font-black text-stone-600 hover:border-brand">-</button>
                    <span class="w-6 text-center text-sm font-black text-stone-900" x-text="parkirUnits"></span>
                    <button type="button" x-on:click="parkirUnits = Math.min(30, parkirUnits + 1)" class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 font-black text-stone-600 hover:border-brand">+</button>
                </div>
            </div>
        </div>

        <!-- Step 5: Result -->
        <div x-show="step === 5" x-cloak>
            <div class="mb-6 text-center">
                <h3 class="font-marketing-display mb-1 text-lg font-black text-stone-900">Hasil Perencanaan Anda</h3>
                <p class="text-sm text-stone-500">Lahan <span class="font-bold text-stone-700" x-text="panjang + ' x ' + lebar + ' m'"></span>, luas <span class="font-bold text-stone-700" x-text="formatM2(luas)"></span></p>
            </div>

            <!-- Option tabs -->
            <div class="mb-5 flex flex-wrap justify-center gap-2">
                <template x-for="(opsi, i) in hasil" :key="i">
                    <button
                        type="button"
                        x-on:click="activeOptionIndex = i"
                        class="rounded-full border-2 px-4 py-2 text-xs font-bold transition-colors"
                        :class="activeOptionIndex === i ? 'border-brand bg-brand text-white' : 'border-stone-200 text-stone-600 hover:border-brand'"
                        x-text="'Opsi ' + (i + 1) + ': ' + opsi.nama"
                    ></button>
                </template>
            </div>

            <template x-if="hasil.length === 0">
                <div class="rounded-xl border-2 border-dashed border-stone-200 p-8 text-center text-sm text-stone-500">
                    Belum ada konfigurasi yang cocok untuk kombinasi lahan dan pilihan Anda. Coba perbesar lahan atau kurangi jumlah fasilitas, atau langsung konsultasi dengan tim kami di bawah.
                </div>
            </template>

            <template x-if="aktif">
                <div>
                    <div class="grid gap-5 md:grid-cols-5">
                        <!-- Left: details -->
                        <div class="md:col-span-2">
                            <div class="mb-4 rounded-2xl border border-stone-200 p-5">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wide text-stone-400">Planning Score</span>
                                    <span class="font-marketing-display text-xl font-black text-brand" x-text="aktif.skor + '/100'"></span>
                                </div>
                                <p class="mb-4 text-[11px] text-stone-400">Skor internal untuk membandingkan opsi, bukan penilaian teknik resmi.</p>

                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-stone-400">Lapangan</p>
                                <ul class="mb-3 space-y-1 text-sm text-stone-700">
                                    <template x-for="(court, i) in aktif.courts" :key="i">
                                        <li class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 flex-shrink-0 text-brand" x-html="ICON_SVG[court.sportKey]"></span>
                                            <span x-text="court.label + ' (' + court.width + ' x ' + court.length + ' m)'"></span>
                                        </li>
                                    </template>
                                </ul>

                                <p class="mb-1 text-xs font-bold uppercase tracking-wide text-stone-400">Fasilitas</p>
                                <ul class="mb-4 space-y-1 text-sm text-stone-700">
                                    <template x-for="(fac, i) in aktif.facilities" :key="i">
                                        <li class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 flex-shrink-0 text-brand" x-html="ICON_SVG[fac.key]"></span>
                                            <span x-text="fac.label"></span>
                                        </li>
                                    </template>
                                    <li class="text-stone-400" x-show="aktif.facilities.length === 0">Tidak ada fasilitas tambahan</li>
                                </ul>

                                <div class="mb-3 flex items-center justify-between border-t border-stone-100 pt-3 text-sm">
                                    <span class="text-stone-500">Estimasi penggunaan lahan</span>
                                    <span class="font-bold text-stone-900" x-text="Math.round(aktif.landUsedPercent) + '%'"></span>
                                </div>

                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold"
                                    :class="{
                                        'bg-brand-100 text-brand': aktif.budgetStatus === 'feasible',
                                        'bg-amber-pale text-amber': aktif.budgetStatus === 'tight',
                                        'bg-danger-pale text-danger': aktif.budgetStatus === 'insufficient',
                                    }"
                                    x-text="budgetStatusLabel(aktif.budgetStatus)"
                                ></span>

                                <p class="mt-4 text-sm italic leading-relaxed text-stone-500" x-text="aktif.alasan"></p>
                            </div>
                        </div>

                        <!-- Right: conceptual layout -->
                        <div class="md:col-span-3">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Tata Letak Konseptual</p>
                                <p class="text-[11px] italic text-stone-400">Bukan gambar teknik</p>
                            </div>
                            <div class="flex min-h-[220px] flex-wrap gap-1.5 rounded-2xl border-2 border-dashed border-stone-200 bg-[#f7f5f2] p-1.5">
                                <template x-for="(blok, i) in aktif.layout" :key="i">
                                    <div
                                        class="flex min-h-[90px] flex-col items-center justify-center gap-1 rounded-xl border p-2 text-center"
                                        :class="blok.tipe === 'lapangan' ? 'border-brand-300 bg-brand-100' : 'border-stone-200 bg-white'"
                                        :style="'flex-basis: ' + blok.persen + '%; flex-grow: 1;'"
                                    >
                                        <span class="flex h-5 w-5 text-brand" x-html="ICON_SVG[blok.key]"></span>
                                        <span class="text-[11px] font-bold leading-tight text-stone-700" x-text="blok.label"></span>
                                        <span class="text-[10px] text-stone-400" x-text="blok.sub"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4 rounded-2xl border border-stone-200 p-4">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="text-stone-500">Budget Anda</span>
                                    <span class="font-bold text-stone-900" x-text="formatRupiah(budgetValue())"></span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-stone-500">Estimasi proyek (opsi ini)</span>
                                    <span class="font-bold text-stone-900" x-text="formatRupiahRange(aktif.estimasiBiaya)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-6 rounded-xl bg-stone-50 p-4 text-xs leading-relaxed text-stone-500">
                        Kalkulator ini memberikan estimasi perencanaan awal saja. Ukuran lapangan aktual, kebutuhan bangunan, parkir, garis sempadan, drainase, kebutuhan struktur, regulasi setempat, dan biaya konstruksi final harus dikonfirmasi oleh tim teknis kami.
                    </p>
                </div>
            </template>

            <!-- Lead form -->
            <div class="mt-8 rounded-2xl border-2 border-brand bg-brand-50 p-6">
                <div class="py-6 text-center" x-show="leadTerkirim" x-cloak>
                    <div class="mb-3 flex justify-center">
                        <x-icon name="check-circle" size="40" class="text-brand" />
                    </div>
                    <h4 class="font-marketing-display mb-1 text-base font-black text-stone-900">Terima kasih!</h4>
                    <p class="text-sm text-stone-500">Jendela WhatsApp akan segera terbuka berisi ringkasan hasil kalkulator Anda.</p>
                </div>

                <div x-show="!leadTerkirim">
                    <h4 class="font-marketing-display mb-1 text-base font-black text-stone-900">Mau Rencana Site yang Lebih Akurat?</h4>
                    <p class="mb-4 text-sm text-stone-500">Kirim detail lahan Anda, tim kami bantu cek tata letak dan opsi konstruksinya.</p>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <input type="text" x-model="leadNama" placeholder="Nama Lengkap"
                               class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none" />
                        <input type="text" x-model="leadWa" placeholder="Nomor WhatsApp"
                               class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none" />
                        <input type="email" x-model="leadEmail" placeholder="Email (opsional)"
                               class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none" />
                        <input type="text" x-model="leadLokasi" placeholder="Lokasi Proyek (kota)"
                               class="w-full rounded-xl border-2 border-stone-200 bg-white px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none" />
                    </div>

                    <small class="mt-2 block text-xs font-semibold text-danger" x-show="leadError" x-cloak x-text="leadError"></small>

                    <button
                        type="button"
                        x-on:click="kirimKonsultasi()"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-4 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-colors hover:bg-brand-dark"
                    >
                        <x-icon name="whatsapp-logo" size="16" class="text-white" />
                        Dapatkan Konsultasi Gratis
                    </button>
                </div>
            </div>
        </div>

        <!-- Nav buttons -->
        <div class="mt-6 flex items-center justify-between" x-show="step < 5">
            <button type="button" x-on:click="back()" x-show="step > 1" class="flex items-center gap-1 rounded-xl border-2 border-stone-200 px-5 py-3 text-sm font-bold text-stone-600 transition-colors hover:border-brand hover:text-brand">
                <x-icon name="chevron-left" size="16" />
                Kembali
            </button>
            <span x-show="step === 1"></span>
            <button
                type="button"
                x-on:click="next()"
                :disabled="!canNext"
                class="ml-auto flex items-center gap-1 rounded-xl bg-brand px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-40"
            >
                <span x-text="step === 4 ? 'Lihat Hasil' : 'Lanjut'"></span>
                <x-icon name="chevron-right" size="16" />
            </button>
        </div>
        <div class="mt-6 text-center" x-show="step === 5">
            <button type="button" x-on:click="reset()" class="text-xs font-bold text-stone-400 underline hover:text-brand">Mulai ulang kalkulator</button>
        </div>
    </div>
</section>

@verbatim
<script>
    // ── KONFIGURASI (bisa diubah kontraktor tanpa sentuh markup/logic) ──
    const PLANNER_CONFIG = {
        sports: {
            futsal: {
                label: 'Futsal',
                variants: {
                    compact:   { width: 15, length: 30, label: 'Futsal Compact' },
                    standard:  { width: 18, length: 35, label: 'Futsal Standard' },
                    large:     { width: 20, length: 40, label: 'Futsal Large' },
                    kompetisi: { width: 22, length: 40, label: 'Futsal Kompetisi' },
                },
                runoffMeters: 2,
                costPerM2: [800000, 1500000],
            },
            miniSoccer: { width: 25, length: 40, runoffMeters: 3, costPerM2: [600000, 1200000], label: 'Mini Soccer' },
            padel:      { width: 10, length: 20, runoffMeters: 1.5, costPerM2: [3000000, 5000000], label: 'Padel' },
            badminton:  { width: 6.1, length: 13.4, runoffMeters: 1.5, costPerM2: [1500000, 2500000], label: 'Badminton' },
            basketball: { width: 15, length: 28, runoffMeters: 2, costPerM2: [700000, 1300000], label: 'Basketball' },
            lainnya:    { width: 15, length: 25, runoffMeters: 2, costPerM2: [700000, 1500000], label: 'Lapangan Lainnya' },
        },
        facilities: {
            parkir:      { m2PerUnit: 12.5, costPerM2: [300000, 600000], label: 'Area Parkir' },
            toilet:      { fixedM2: 12, costPerM2: [2500000, 3500000], label: 'Toilet' },
            ruangGanti:  { fixedM2: 20, costPerM2: [2500000, 3500000], label: 'Ruang Ganti' },
            kafe:        { fixedM2: 25, costPerM2: [3000000, 4500000], label: 'Kafe' },
            resepsionis: { fixedM2: 9, costPerM2: [2500000, 4000000], label: 'Resepsionis' },
            musala:      { fixedM2: 12, costPerM2: [2500000, 3500000], label: 'Musala' },
            gym:         { fixedM2: 40, costPerM2: [3000000, 4500000], label: 'Gym' },
            retail:      { fixedM2: 15, costPerM2: [3000000, 4500000], label: 'Area Retail' },
            gudang:      { fixedM2: 9, costPerM2: [2000000, 3000000], label: 'Gudang' },
            lainnya:     { fixedM2: 15, costPerM2: [2500000, 4000000], label: 'Fasilitas Lainnya' },
        },
        circulationFactor: 0.12,
        budgetBands: {
            di_bawah_1m: [0, 1000000000],
            satu_dua_m: [1000000000, 2000000000],
            dua_tiga_m: [2000000000, 3000000000],
            tiga_lima_m: [3000000000, 5000000000],
            lima_plus_m: [5000000000, 8000000000],
        },
        scoringWeights: { landFit: 0.4, budgetFit: 0.3, facilityCompleteness: 0.15, priorities: 0.15 },
    };

    // ── ICON MAP untuk render dinamis lewat x-html (hasil kalkulator
    // dibangun di JS, bukan Blade, jadi tidak bisa panggil <x-icon>
    // langsung; markup SVG di bawah sengaja disamakan dengan partial
    // Blade-nya di resources/views/icons/) ──
    const ICON_SVG = {
        futsal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><rect x="3" y="7" width="18" height="11" rx="0.5"/><path d="M3 11h18M8 7v11M16 7v11"/></svg>',
        miniSoccer: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><circle cx="12" cy="12" r="9"/><path d="M12 8l3.5 2.5-1.3 4h-4.4l-1.3-4L12 8Z"/></svg>',
        padel: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><ellipse cx="12" cy="9" rx="6" ry="7"/><path d="M12 16v6"/></svg>',
        badminton: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M12 3l7 7-11 11-3-3L16 7"/><path d="M4 20l3-3"/></svg>',
        basketball: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18M5.5 5.5c3 3 3 10 0 13M18.5 5.5c-3 3-3 10 0 13"/></svg>',
        lainnya: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M12 5v14M5 12h14"/></svg>',
        parkir: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 17V7h3.5a3 3 0 0 1 0 6H9"/></svg>',
        toilet: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M7 4h10v3a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4Z"/><path d="M6 11c0-1 .5-2 2-2h8c1.5 0 2 1 2 2 0 5-2.5 9-6 9s-6-4-6-9Z"/></svg>',
        ruangGanti: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M4 8a8 8 0 0 1 14-5"/><path d="M6 8h14"/><path d="M8 12v2M12 12v2M16 12v2M10 16v2M14 16v2"/></svg>',
        kafe: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M5 8h11v6a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5V8Z"/><path d="M16 9h1.5a2.5 2.5 0 0 1 0 5H16"/><path d="M8 4c0 1-1 1-1 2M12 4c0 1-1 1-1 2"/></svg>',
        resepsionis: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 8h1M14 8h1M9 12h1M14 12h1M9 16h1M14 16h1"/></svg>',
        musala: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M12 3v2M9 8a3 3 0 0 1 6 0c0 1.5-1 2-1 3.5H10c0-1.5-1-2-1-3.5Z"/><path d="M5 21v-6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v6"/><path d="M5 21h14M11 21v-4h2v4"/></svg>',
        gym: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M4 9v6M7 7v10M17 7v10M20 9v6M7 12h10"/></svg>',
        retail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M6 8h12l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
        gudang: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full"><path d="M3 8l9-4 9 4-9 4-9-4Z"/><path d="M3 8v9l9 4 9-4V8"/><path d="M12 12v9"/></svg>',
    };

    // ── MESIN HITUNG: fungsi murni, tidak menyentuh DOM/Alpine ──
    const PlannerCalculator = (function () {
        function luasLapangan(sportKey, variantKey, config) {
            const sport = config.sports[sportKey];
            const dim = sportKey === 'futsal' ? sport.variants[variantKey || 'standard'] : sport;
            const runoff = sport.runoffMeters;
            const width = dim.width + runoff * 2;
            const length = dim.length + runoff * 2;
            return {
                sportKey,
                variantKey: sportKey === 'futsal' ? (variantKey || 'standard') : null,
                label: dim.label,
                width: Math.round(dim.width * 10) / 10,
                length: Math.round(dim.length * 10) / 10,
                areaWithRunoff: width * length,
                costRange: [sport.costPerM2[0] * dim.width * dim.length, sport.costPerM2[1] * dim.width * dim.length],
            };
        }

        function luasFasilitas(key, config, units) {
            const fac = config.facilities[key];
            const m2 = fac.m2PerUnit ? fac.m2PerUnit * (units || 1) : fac.fixedM2;
            return { key, label: fac.label, m2, costRange: [fac.costPerM2[0] * m2, fac.costPerM2[1] * m2] };
        }

        function totalWithCirculation(m2, config) {
            return m2 * (1 + config.circulationFactor);
        }

        function landFitScore(pct) {
            if (pct > 100) return 0;
            if (pct >= 70 && pct <= 95) return 1;
            if (pct < 70) return pct / 70;
            return 1 - ((pct - 95) / 5) * 0.4;
        }

        function budgetStatusAndScore(estimasiBiaya, userBudget) {
            const [uMin, uMax] = userBudget;
            const [oMin, oMax] = estimasiBiaya;
            if (uMax >= oMax) return { status: 'feasible', score: 1 };
            if (uMax >= oMin) return { status: 'tight', score: 0.6 };
            return { status: 'insufficient', score: 0.2 };
        }

        function scoreOption(option, input, config) {
            const landFit = landFitScore(option.landUsedPercent);
            const budget = budgetStatusAndScore(option.estimasiBiaya, input.userBudget);
            const requested = input.facilities.length || 1;
            const included = option.facilities.filter((f) => input.facilities.includes(f.key)).length;
            const facilityCompleteness = input.facilities.length === 0 ? 1 : included / requested;
            const sportsIncluded = new Set(option.courts.map((c) => c.sportKey));
            const priorities = input.sports.length === 0 ? 1 : input.sports.filter((s) => sportsIncluded.has(s)).length / input.sports.length;

            const weights = config.scoringWeights;
            const skor = Math.round(
                100 * (landFit * weights.landFit + budget.score * weights.budgetFit + facilityCompleteness * weights.facilityCompleteness + priorities * weights.priorities)
            );

            return { ...option, budgetStatus: budget.status, skor: Math.max(0, Math.min(100, skor)) };
        }

        function buildLayout(option) {
            const totalArea = option.courts.reduce((s, c) => s + c.areaWithRunoff, 0) + option.facilities.reduce((s, f) => s + f.m2, 0);
            const blocks = [];
            option.courts.forEach((c) => {
                blocks.push({ tipe: 'lapangan', key: c.sportKey, label: c.label, sub: c.width + ' x ' + c.length + ' m', persen: Math.max(18, Math.round((c.areaWithRunoff / totalArea) * 100)) });
            });
            option.facilities.forEach((f) => {
                blocks.push({ tipe: 'fasilitas', key: f.key, label: f.label, sub: Math.round(f.m2) + ' m2', persen: Math.max(12, Math.round((f.m2 / totalArea) * 100)) });
            });
            return blocks;
        }

        function makeOption(nama, courts, facilityKeys, alasan, input, config, parkirUnits) {
            const facilities = facilityKeys
                .filter((k) => k !== 'lainnya' && config.facilities[k])
                .map((k) => luasFasilitas(k, config, k === 'parkir' ? parkirUnits : 1));

            const rawArea = courts.reduce((s, c) => s + c.areaWithRunoff, 0) + facilities.reduce((s, f) => s + f.m2, 0);
            const landUsedM2 = totalWithCirculation(rawArea, config);
            const landUsedPercent = input.area > 0 ? (landUsedM2 / input.area) * 100 : 0;

            const costMin = courts.reduce((s, c) => s + c.costRange[0], 0) + facilities.reduce((s, f) => s + f.costRange[0], 0);
            const costMax = courts.reduce((s, c) => s + c.costRange[1], 0) + facilities.reduce((s, f) => s + f.costRange[1], 0);

            const option = {
                nama,
                courts,
                facilities,
                landUsedM2,
                landUsedPercent,
                estimasiBiaya: [costMin, costMax],
                alasan,
            };
            option.layout = buildLayout(option);
            return scoreOption(option, input, config);
        }

        function buildOptions(input, config) {
            const { sports, futsalVariant, facilities, parkirUnits } = input;
            if (sports.length === 0) return [];

            const options = [];
            const primary = sports.find((s) => s !== 'lainnya') || sports[0];

            // Opsi 1: Konfigurasi Seimbang, satu lapangan tiap olahraga terpilih
            const balancedCourts = sports.filter((s) => config.sports[s]).map((s) => luasLapangan(s, s === 'futsal' ? futsalVariant : null, config));
            if (balancedCourts.length > 0) {
                options.push(
                    makeOption(
                        'Konfigurasi Seimbang',
                        balancedCourts,
                        facilities,
                        'Opsi ini memberi keseimbangan antara ukuran lapangan, fasilitas pendukung, dan sisa lahan.',
                        input,
                        config,
                        parkirUnits
                    )
                );
            }

            // Opsi 2: Maksimal jumlah lapangan (varian terkecil dari olahraga utama)
            if (config.sports[primary]) {
                const compactCourt = luasLapangan(primary, 'compact', config);
                const minimalFacilities = facilities.filter((f) => ['toilet', 'parkir'].includes(f));
                const reservedForFacilities = minimalFacilities.reduce((s, f) => {
                    const fac = config.facilities[f];
                    return s + (fac.m2PerUnit ? fac.m2PerUnit * Math.min(parkirUnits, 3) : fac.fixedM2);
                }, 0);
                const usableForCourts = input.area - reservedForFacilities * (1 + config.circulationFactor);
                const maxCount = Math.floor(usableForCourts / (compactCourt.areaWithRunoff * (1 + config.circulationFactor)));

                if (maxCount >= 2) {
                    const courts = Array.from({ length: Math.min(maxCount, 4) }, () => luasLapangan(primary, 'compact', config));
                    options.push(
                        makeOption(
                            'Maksimal Jumlah Lapangan',
                            courts,
                            minimalFacilities,
                            'Opsi ini memaksimalkan jumlah lapangan yang bisa dibangun, dengan fasilitas pendukung seminimal mungkin.',
                            input,
                            config,
                            Math.min(parkirUnits, 3)
                        )
                    );
                }
            }

            // Opsi 3: Fasilitas lebih lengkap
            if (config.sports[primary]) {
                const upgradedVariant = primary === 'futsal' ? (futsalVariant === 'compact' ? 'standard' : 'large') : null;
                const upgradedCourt = luasLapangan(primary, upgradedVariant, config);
                const extraFacilities = Array.from(new Set([...facilities.filter((f) => f !== 'lainnya'), 'toilet', 'ruangGanti', 'resepsionis', 'parkir']));
                options.push(
                    makeOption(
                        'Fasilitas Lebih Lengkap',
                        [upgradedCourt],
                        extraFacilities,
                        'Opsi ini cocok kalau Anda ingin membangun sport center dengan fasilitas paling lengkap sejak awal.',
                        input,
                        config,
                        Math.max(parkirUnits, 6)
                    )
                );
            }

            return options
                .filter((o) => o.landUsedPercent <= 140)
                .sort((a, b) => b.skor - a.skor)
                .slice(0, 3);
        }

        return { buildOptions, luasLapangan, luasFasilitas };
    })();

    // ── STATE UI (Alpine), murni memanggil PlannerCalculator ──
    function plannerApp() {
        return {
            step: 1,
            stepLabels: ['Lahan', 'Budget', 'Olahraga', 'Fasilitas', 'Hasil'],
            panjang: null,
            lebar: null,
            landChoice: null,
            budgetKey: null,
            budgetCustom: null,
            selectedSports: [],
            futsalVariant: 'standard',
            selectedFacilities: [],
            parkirUnits: 6,
            hasil: [],
            activeOptionIndex: 0,
            leadNama: '',
            leadWa: '',
            leadEmail: '',
            leadLokasi: '',
            leadError: '',
            leadTerkirim: false,

            get luas() {
                return this.panjang > 0 && this.lebar > 0 ? this.panjang * this.lebar : 0;
            },

            get aktif() {
                return this.hasil[this.activeOptionIndex] || null;
            },

            get canNext() {
                if (this.step === 1) return this.luas > 0;
                if (this.step === 2) return !!this.budgetKey && (this.budgetKey !== 'custom' || this.budgetCustom > 0);
                if (this.step === 3) return this.selectedSports.length > 0;
                return true;
            },

            toggleSport(key) {
                const i = this.selectedSports.indexOf(key);
                if (i === -1) this.selectedSports.push(key);
                else this.selectedSports.splice(i, 1);
            },

            toggleFacility(key) {
                const i = this.selectedFacilities.indexOf(key);
                if (i === -1) this.selectedFacilities.push(key);
                else this.selectedFacilities.splice(i, 1);
            },

            budgetValue() {
                if (this.budgetKey === 'custom') return [this.budgetCustom, this.budgetCustom];
                return PLANNER_CONFIG.budgetBands[this.budgetKey] || [0, 0];
            },

            next() {
                if (!this.canNext) return;
                if (this.step === 4) {
                    this.hasil = PlannerCalculator.buildOptions(
                        {
                            area: this.luas,
                            userBudget: this.budgetValue(),
                            sports: this.selectedSports,
                            futsalVariant: this.futsalVariant,
                            facilities: this.selectedFacilities,
                            parkirUnits: this.parkirUnits,
                        },
                        PLANNER_CONFIG
                    );
                    this.activeOptionIndex = 0;
                }
                this.step++;
            },

            back() {
                if (this.step > 1) this.step--;
            },

            reset() {
                this.step = 1;
                this.hasil = [];
                this.leadTerkirim = false;
            },

            formatM2(n) {
                return new Intl.NumberFormat('id-ID').format(Math.round(n)) + ' m2';
            },

            formatRupiah(n) {
                if (!n) return 'Rp 0';
                if (n >= 1000000000) return 'Rp ' + (Math.round((n / 1000000000) * 10) / 10).toString().replace('.', ',') + ' M';
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n / 1000000)) + ' Jt';
            },

            formatRupiahRange(range) {
                return this.formatRupiah(range[0]) + ' - ' + this.formatRupiah(range[1]);
            },

            budgetStatusLabel(status) {
                if (status === 'feasible') return 'Kemungkinan sesuai budget';
                if (status === 'tight') return 'Budget cukup ketat';
                return 'Budget kemungkinan kurang';
            },

            kirimKonsultasi() {
                this.leadError = '';
                if (!this.leadNama.trim()) { this.leadError = 'Nama lengkap wajib diisi.'; return; }
                if (!this.leadWa.trim()) { this.leadError = 'Nomor WhatsApp wajib diisi.'; return; }
                if (!this.leadLokasi.trim()) { this.leadError = 'Lokasi proyek wajib diisi.'; return; }

                const opsi = this.aktif;
                const sportsLabel = this.selectedSports.map((s) => (PLANNER_CONFIG.sports[s] ? PLANNER_CONFIG.sports[s].label || s : s)).join(', ') || 'Belum ditentukan';
                const facilitiesLabel = this.selectedFacilities.map((f) => (PLANNER_CONFIG.facilities[f] ? PLANNER_CONFIG.facilities[f].label : f)).join(', ') || 'Belum ditentukan';
                const rekomendasi = opsi ? opsi.courts.map((c) => c.label).join(', ') + ' plus ' + opsi.facilities.map((f) => f.label).join(', ') : 'Belum ada rekomendasi';

                const waText = encodeURIComponent(
                    'Halo kak, saya baru coba kalkulator perencanaan sport center di website 🙏\n\n' +
                    '━━━━━━━━━━━━━━━━━━━━\n' +
                    '👤 *INFORMASI PEMESAN*\n' +
                    'Nama       : ' + this.leadNama + '\n' +
                    'WhatsApp   : ' + this.leadWa + '\n' +
                    'Email      : ' + (this.leadEmail || 'Tidak diisi') + '\n' +
                    'Lokasi     : ' + this.leadLokasi + '\n' +
                    '━━━━━━━━━━━━━━━━━━━━\n' +
                    '📐 *HASIL KALKULATOR*\n' +
                    'Lahan      : ' + this.panjang + ' x ' + this.lebar + ' m (' + this.formatM2(this.luas) + ')\n' +
                    'Budget     : ' + this.formatRupiah(this.budgetValue()[1]) + '\n' +
                    'Olahraga   : ' + sportsLabel + '\n' +
                    'Fasilitas  : ' + facilitiesLabel + '\n' +
                    'Rekomendasi: ' + rekomendasi + '\n' +
                    '━━━━━━━━━━━━━━━━━━━━\n' +
                    'Mohon bantu review lebih lanjut ya kak, terima kasih! 😊'
                );

                this.leadTerkirim = true;
                setTimeout(() => {
                    window.open('https://wa.me/6281357570064?text=' + waText, '_blank', 'noopener,noreferrer');
                }, 400);
            },
        };
    }
</script>
@endverbatim
