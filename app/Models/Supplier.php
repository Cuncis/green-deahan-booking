<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier toko online (dropship). Hanya terlihat oleh superadmin.
 */
#[Fillable(['name', 'platform', 'shop_url', 'whatsapp', 'origin_postal_code', 'notes'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    public const PLATFORM = ['shopee', 'tokopedia', 'direct'];

    /**
     * Supplier untuk isian awal form produk hasil impor HTML: nama toko
     * Shopee yang sama persis, kalau tidak ada maka outlet yang areanya
     * sama dengan kota "Dikirim Dari". Hanya dipilih bila hasilnya satu,
     * kalau ada beberapa outlet di kota itu pilihan diserahkan ke admin.
     */
    public static function cocokDariImpor(?string $namaToko, ?string $dikirimDari): ?self
    {
        if ($namaToko) {
            $sama = self::whereRaw('lower(name) = ?', [mb_strtolower($namaToko)])->first();

            if ($sama) {
                return $sama;
            }
        }

        $kota = self::normalisasiKota($dikirimDari);

        if ($kota === '') {
            return null;
        }

        $kandidat = self::whereNotNull('origin_postal_code')->get(['id', 'name', 'notes', 'origin_postal_code']);

        $areaSama = $kandidat->filter(fn (self $supplier) => $supplier->areaTercatat() === $kota);

        if ($areaSama->count() === 1) {
            return $areaSama->first();
        }

        if ($areaSama->count() > 1) {
            return null;
        }

        $namaMengandung = $kandidat->filter(fn (self $supplier) => str_contains(self::normalisasiKota($supplier->name), $kota));

        return $namaMengandung->count() === 1 ? $namaMengandung->first() : null;
    }

    /** Area outlet dari baris "Area: ..." di catatan, sudah dinormalisasi. */
    public function areaTercatat(): string
    {
        return preg_match('/^Area:\s*(.+)$/mu', (string) $this->notes, $cocok) ? self::normalisasiKota($cocok[1]) : '';
    }

    private static function normalisasiKota(?string $teks): string
    {
        $teks = mb_strtolower(trim((string) $teks));
        $teks = preg_replace('/\b(kota adm\.?|kota|kabupaten|kab\.?|administrasi)\b/u', ' ', $teks) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $teks) ?? '');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
