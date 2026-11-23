<?php

namespace App\Services;

use App\Models\Product;
use DOMDocument;
use DOMXPath;

/**
 * Membaca HTML halaman produk marketplace (Shopee) yang ditempel superadmin
 * dan mengambil hanya data penting untuk form produk toko. Hasilnya cuma
 * saran isi form: tidak ada yang disimpan di sini, HTML mentah tidak
 * disimpan, dan ulasan, nama pembeli, rating, voucher, serta produk lain
 * sengaja tidak dibaca.
 *
 * Susunan HTML Shopee bisa berubah. Bagian yang tidak ditemukan dikembalikan
 * null, bukan galat.
 */
class ImporProdukHtmlService
{
    /** Batas teks deskripsi, sama dengan batas di ProdukRequest. */
    public const MAKS_DESKRIPSI = 3000;

    /**
     * @return array{nama: ?string, deskripsi: ?string, spesifikasi: array<int, array{nama: string, nilai: string}>, dikirimDari: ?string, kategori: ?string, linkSupplier: ?string, namaSupplier: ?string, gambarUrl: ?string, gambarUrls: array<int, string>, hargaShopee: ?int, hargaRetail: ?int}
     */
    public function baca(string $html): array
    {
        $xpath = $this->muat($html);

        return [
            'nama' => $this->nama($xpath),
            'deskripsi' => $this->deskripsi($xpath),
            'spesifikasi' => $this->spesifikasi($xpath),
            'dikirimDari' => $this->dikirimDari($xpath),
            'kategori' => $this->kategori($xpath),
            'linkSupplier' => $this->linkSupplier($xpath),
            'namaSupplier' => $this->namaSupplier($xpath),
            'gambarUrl' => $this->gambarUrls($xpath)[0] ?? null,
            'gambarUrls' => $this->gambarUrls($xpath),
            'hargaShopee' => $this->hargaShopee($xpath),
            'hargaRetail' => $this->hargaRetail($xpath),
        ];
    }

    /**
     * Gambar hanya boleh diunduh dari CDN Shopee lewat https, tanpa port
     * khusus dan tanpa kredensial di URL.
     */
    public static function gambarDiizinkan(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $bagian = parse_url($url);

        if (! is_array($bagian) || ($bagian['scheme'] ?? '') !== 'https' || isset($bagian['port']) || isset($bagian['user']) || isset($bagian['pass'])) {
            return false;
        }

        $host = strtolower($bagian['host'] ?? '');

        return ($host === 'susercontent.com' || str_ends_with($host, '.susercontent.com'))
            && str_starts_with($bagian['path'] ?? '', '/file/');
    }

    private function muat(string $html): DOMXPath
    {
        $dokumen = new DOMDocument;
        $sebelumnya = libxml_use_internal_errors(true);
        $dokumen->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        return new DOMXPath($dokumen);
    }

    private function nama(DOMXPath $xpath): ?string
    {
        $judul = $xpath->query('//h1')->item(0)?->textContent;

        return $this->bersihkanBaris($judul) ?: null;
    }

    private function deskripsi(DOMXPath $xpath): ?string
    {
        $bagian = $xpath->query("//h2[normalize-space(.)='Deskripsi Produk']/parent::*")->item(0);

        if ($bagian === null) {
            return null;
        }

        $baris = [];

        foreach ($xpath->query('.//p', $bagian) as $paragraf) {
            foreach (preg_split('/\R/u', $paragraf->textContent) ?: [] as $teks) {
                $baris[] = $this->bersihkanBaris($teks);
            }
        }

        $isi = $this->susunDeskripsi($baris);

        return $isi === '' ? null : $isi;
    }

    /**
     * Membuang bagian khas toko Shopee yang tidak berlaku di toko kita:
     * blok "Note" (keaslian, garansi, penukaran), daftar outlet penjual,
     * harga retail, hashtag, dan salam penjual. Teks tentang produknya sendiri dipertahankan.
     *
     * @param  array<int, string>  $baris
     */
    private function susunDeskripsi(array $baris): string
    {
        $dipakai = [];

        foreach ($baris as $teks) {
            if (preg_match('/^(note|catatan|perhatian|ketentuan)\s*:/iu', $teks)) {
                break;
            }

            // Daftar outlet/cabang penjual (tersebar di banyak kota) tidak berguna bagi pembeli: buang dari sini sampai akhir.
            if (preg_match('/^📍|\d+\s+(outlet|cabang|toko)\s+tersebar|^lokasi\s+\S+.*\b(outlet|cabang)\b/iu', $teks)) {
                break;
            }

            $teks = trim(preg_replace('/#\S+/u', '', $teks) ?? '');

            if ($teks === ''
                || preg_match('/\bRp\.?\s*\d/iu', $teks)
                // "harga bandrol 40.000", "harga normal 1.250.000": harga Shopee tanpa awalan Rp.
                || preg_match('/\bharga\b.*\d{1,3}(?:\.\d{3})+/iu', $teks)
                || preg_match('/original\s*100\s*%|100\s*%\s*original|uang\s+kembali/iu', $teks)
                || preg_match('/happy\s+shopping|stok\s+terbatas|jangan\s+sampai\s+kehabisan/iu', $teks)) {
                $dipakai[] = '';

                continue;
            }

            $dipakai[] = $teks;
        }

        $paragraf = preg_split('/(?:\R\s*){2,}/u', trim(implode("\n", $dipakai))) ?: [];
        $paragraf = array_filter(array_map(fn (string $teks) => trim(preg_replace('/[ \t]*\R[ \t]*/u', "\n", $teks) ?? ''), $paragraf));

        return trim(mb_substr(implode("\n\n", $paragraf), 0, self::MAKS_DESKRIPSI));
    }

    /**
     * Baris "Spesifikasi Produk" (judul h3 lalu isinya) yang berguna bagi
     * pembeli: merek, bahan, asal produk, dan sebagainya. Kategori dan stok
     * dibuang (sudah ada di toko kita), dan "Dikirim Dari" dibaca terpisah
     * karena bukan spesifikasi barang.
     *
     * @return array<int, array{nama: string, nilai: string}>
     */
    private function spesifikasi(DOMXPath $xpath): array
    {
        $dibuang = ['kategori', 'stok', 'dikirim dari', 'pengiriman'];
        $hasil = [];

        foreach ($this->barisSpesifikasi($xpath) as $nama => $nilai) {
            if (in_array(mb_strtolower($nama), $dibuang, true) || $nilai === '') {
                continue;
            }

            $hasil[] = ['nama' => mb_substr($nama, 0, 60), 'nilai' => mb_substr($nilai, 0, 150)];
        }

        return array_slice($hasil, 0, Product::MAKS_SPESIFIKASI);
    }

    private function dikirimDari(DOMXPath $xpath): ?string
    {
        foreach ($this->barisSpesifikasi($xpath) as $nama => $nilai) {
            if (mb_strtolower($nama) === 'dikirim dari' && $nilai !== '') {
                return mb_substr($nilai, 0, 100);
            }
        }

        return null;
    }

    /**
     * @return array<string, string> judul baris menjadi isinya
     */
    private function barisSpesifikasi(DOMXPath $xpath): array
    {
        $bagian = $xpath->query("//h2[normalize-space(.)='Spesifikasi Produk']/parent::*")->item(0);

        if ($bagian === null) {
            return [];
        }

        $baris = [];

        foreach ($xpath->query('.//h3', $bagian) as $judul) {
            $nilai = '';

            for ($saudara = $judul->nextSibling; $saudara !== null; $saudara = $saudara->nextSibling) {
                $nilai .= ' '.$saudara->textContent;
            }

            $nama = $this->bersihkanBaris($judul->textContent);
            $nilai = $this->bersihkanBaris($nilai);

            if ($nama !== '' && ! isset($baris[$nama])) {
                $baris[$nama] = $nilai;
            }
        }

        return $baris;
    }

    private function kategori(DOMXPath $xpath): ?string
    {
        foreach ($xpath->query("//a[contains(@href,'-cat.')]") as $tautan) {
            if (preg_match('/olahraga/iu', $tautan->textContent) && array_key_exists('olahraga', Product::KATEGORI)) {
                return 'olahraga';
            }
        }

        return null;
    }

    private function linkSupplier(DOMXPath $xpath): ?string
    {
        foreach ($xpath->query('//a[@href]') as $tautan) {
            $href = $tautan->getAttribute('href');

            if (preg_match('#/(?:from_same_shop|you_may_also_like)/(\d+)/(\d+)/#', $href, $cocok)) {
                return "https://shopee.co.id/product/{$cocok[1]}/{$cocok[2]}";
            }
        }

        return null;
    }

    private function namaSupplier(DOMXPath $xpath): ?string
    {
        $nama = $xpath->query("(//a[contains(@href,'entryPoint=ShopByPDP')])[1]/parent::*/following-sibling::*[1]/*[1]")->item(0)?->textContent;

        return $this->bersihkanBaris($nama) ?: null;
    }

    /**
     * Foto utama lalu thumbnail galeri (maksimal FotoProdukService::MAKS_FOTO),
     * tanpa duplikat. Hanya gambar galeri produk: foto ulasan dan produk lain
     * tidak dibaca.
     *
     * @return array<int, string>
     */
    private function gambarUrls(DOMXPath $xpath): array
    {
        $kandidat = [];

        foreach ($xpath->query("//img[starts-with(@alt,'Product image')]") as $gambar) {
            $kandidat[] = $gambar->getAttribute('src');
        }

        foreach ($xpath->query("//img[@width='82']") as $gambar) {
            $kandidat[] = $gambar->getAttribute('src');
        }

        foreach ($xpath->query("//meta[@property='og:image']") as $meta) {
            $kandidat[] = $meta->getAttribute('content');
        }

        $hasil = [];

        foreach ($kandidat as $url) {
            $url = (string) strtok($url, '?#');
            $dasar = preg_replace('/@resize.*$/', '', $url) ?? $url;

            if (self::gambarDiizinkan($dasar) && ! in_array($dasar, $hasil, true)) {
                $hasil[] = $dasar;
            }
        }

        return array_slice($hasil, 0, FotoProdukService::MAKS_FOTO);
    }

    private function hargaShopee(DOMXPath $xpath): ?int
    {
        $teks = $xpath->query("//section[@aria-live='polite']")->item(0)?->textContent;

        if ($teks !== null && preg_match('/Rp\s*([\d.]+)/u', $teks, $cocok)) {
            $harga = (int) str_replace('.', '', $cocok[1]);

            return $harga > 0 ? $harga : null;
        }

        return null;
    }

    /**
     * Harga retail yang ditulis penjual di deskripsi ("HARGA RETAIL Rp.199.800"),
     * dipakai sebagai saran harga jual. Baris ini dibuang dari deskripsi.
     */
    private function hargaRetail(DOMXPath $xpath): ?int
    {
        $bagian = $xpath->query("//h2[normalize-space(.)='Deskripsi Produk']/parent::*")->item(0);

        if ($bagian !== null && preg_match('/retail\D{0,20}Rp\.?\s*([\d.]+)/iu', $this->bersihkanBaris($bagian->textContent), $cocok)) {
            $harga = (int) str_replace('.', '', rtrim($cocok[1], '.'));

            return $harga > 0 ? $harga : null;
        }

        return null;
    }

    /**
     * Rapikan satu baris: spasi non-pecah, emoji, dan tanda strip panjang
     * (tidak dipakai di teks situs).
     */
    private function bersihkanBaris(?string $teks): string
    {
        $teks = str_replace(["\u{00A0}", '—', '–'], [' ', '-', '-'], (string) $teks);
        $teks = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u', '', $teks) ?? '';

        return trim(preg_replace('/[ \t]+/u', ' ', $teks) ?? '');
    }
}
